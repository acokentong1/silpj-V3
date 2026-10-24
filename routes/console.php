<?php

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('silpj:system-admin {email} {--revoke}', function () {
    $user = User::where('email', $this->argument('email'))->first();
    if (! $user) {
        $this->error('Akun tidak ditemukan. Buat akun melalui pengaturan awal atau admin sekolah.');

        return 1;
    }
    if (! $user->is_active) {
        $this->error('Aktifkan akun sebelum mengubah kewenangan sistem.');

        return 1;
    }
    $grant = ! $this->option('revoke');
    if (! $this->confirm(($grant ? 'Berikan' : 'Cabut').' kewenangan administrator sistem untuk '.$user->email.'?', false)) {
        return 1;
    }
    DB::transaction(function () use ($user, $grant) {
        $user->is_system_admin = $grant;
        $user->save();
        DB::table('system_events')->insert(['actor_id' => $user->id, 'action' => $grant ? 'cli_berikan_admin_sistem' : 'cli_cabut_admin_sistem',
            'details' => json_encode(['source' => 'trusted_server_console', 'target_user_id' => $user->id]), 'created_at' => now(), 'updated_at' => now()]);
    });
    $this->info('Kewenangan sistem diperbarui. Hak akses data sekolah tetap dibatasi ke sekolah akun.');

    return 0;
})->purpose('Mengelola administrator sistem dari konsol server tepercaya');

Artisan::command('silpj:backup {--keep=14}', function () {
    try {
        $service = app(BackupService::class);
        $file = $service->create();
        $service->prune((int) $this->option('keep'));
        Log::info('Cadangan SILPJ berhasil', ['file' => basename($file)]);
        $this->info($file);

        return 0;
    } catch (Throwable $e) {
        Log::error('Cadangan SILPJ gagal', ['error' => $e->getMessage()]);
        $this->error($e->getMessage());

        return 1;
    }
})->purpose('Cadangan SQLite dan berkas dengan checksum serta retensi');
Artisan::command('silpj:backup-due', function () {
    try {
        return \Illuminate\Support\Facades\Cache::lock('silpj-backup-due', 3600)->block(5, function () {
            $path = storage_path('app/private/backup-status.json');
            $status = is_file($path) ? json_decode(file_get_contents($path), true) : null;
            $today = now('Asia/Makassar')->toDateString();
            if (($status['ok'] ?? false) && isset($status['at'], $status['file'])
                && \Carbon\Carbon::parse($status['at'])->setTimezone('Asia/Makassar')->toDateString() === $today
                && is_file(storage_path('app/private/v3-backups/'.basename($status['file'])))) {
                $this->info('Cadangan hari ini sudah tersedia.');
                return 0;
            }
            return $this->call('silpj:backup', ['--keep'=>14]);
        });
    } catch (Throwable $e) {
        Log::error('Backup otomatis gagal', ['error'=>$e->getMessage()]);
        $this->error('Backup otomatis gagal. Periksa log dan halaman Cadangan.');
        return 1;
    }
})->purpose('Backup harian dengan pemeriksaan jadwal yang terlewat');
Schedule::command('silpj:backup-due')->hourly()->withoutOverlapping();
Artisan::command('silpj:restore {file} {--reason=}', function () {
    if (mb_strlen(trim($this->option('reason') ?? '')) < 5) {
        $this->error('Isi --reason minimal 5 karakter.');

        return 1;
    }
    if (! $this->confirm('Ganti seluruh database dan aset semua sekolah dari cadangan? Pastikan server dan worker berhenti.', false)) {
        return 1;
    }
    try {
        app(BackupService::class)->restore($this->argument('file'));
        Log::warning('Pemulihan SILPJ selesai', ['reason' => $this->option('reason'), 'file' => basename($this->argument('file'))]);
        $this->info('Pemulihan selesai. Periksa sistem sebelum artisan up.');

        return 0;
    } catch (Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }
})->purpose('Pemulihan terkontrol dalam maintenance melalui konsol server');
Artisan::command('silpj:storage', function () {
    $disk = Storage::disk('local');
    $bytes = 0;
    $count = 0;
    foreach ($disk->allFiles('v3') as $file) {
        $bytes += $disk->size($file);
        $count++;
    }
    $db = config('database.connections.silpj_v3.database');
    $this->table(['Bagian', 'Ukuran / jumlah'], [['Database', is_file($db) ? filesize($db).' byte' : 'memory'], ['Berkas', $count], ['Total berkas', $bytes.' byte'], ['Snapshot dokumen', DB::table('v3_documents')->selectRaw('COALESCE(SUM(LENGTH(snapshot)),0) AS size')->value('size').' byte']]);
});

Schedule::call(function () {
    $disk = Storage::disk('local');
    foreach ($disk->allFiles('v3-restore-pending') as $file) {
        if ($disk->lastModified($file) < now()->subDays(7)->timestamp) {
            $disk->delete($file);
        }
    }
})->daily()->name('silpj-clean-restore-staging')->withoutOverlapping();
