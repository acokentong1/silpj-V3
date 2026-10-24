<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    public function create(): string
    {
        return Cache::lock('silpj-backup', 3600)->block(5, function () {
            $dir = storage_path('app/private/v3-backups');
            File::ensureDirectoryExists($dir, 0700);
            $base = $dir.'/'.date('Ymd-His').'-'.bin2hex(random_bytes(8));
            $db = $base.'.sqlite';
            $path = $base.'.zip';
            $zip = new \ZipArchive;
            $opened = false;
            try {
                DB::statement("VACUUM INTO '".str_replace("'", "''", $db)."'");
                if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) {
                    throw new \RuntimeException('Gagal membuat ZIP.');
                }
                $opened = true;
                $files = ['database/silpj-v3.sqlite' => $db];
                foreach (Storage::disk('local')->allFiles('v3') as $file) {
                    $files['storage/app/private/'.$file] = Storage::disk('local')->path($file);
                }
                $hashes = [];
                foreach ($files as $entry => $source) {
                    if (! $zip->addFile($source, $entry)) {
                        throw new \RuntimeException('Gagal menambahkan berkas cadangan.');
                    }
                    $hashes[$entry] = hash_file('sha256', $source);
                }
                $zip->addFromString('SILPJ-V3-BACKUP.json', json_encode(['product' => 'silpj-v3', 'schema' => 3, 'created_at' => now()->toIso8601String(), 'sha256' => $hashes], JSON_THROW_ON_ERROR));
                if (! $zip->close()) {
                    throw new \RuntimeException('Gagal menyelesaikan ZIP.');
                }
                $opened = false;
                $this->validate($path);

                return $path;
            } catch (\Throwable $e) {
                if ($opened) {
                    $zip->close();
                }@unlink($path);
                throw $e;
            } finally {
                if (is_file($db)) {
                    unlink($db);
                }
            }
        });
    }

    public function validate(string $path): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Berkas ZIP tidak valid.');
        }
        try {
            $manifestStat = $zip->statName('SILPJ-V3-BACKUP.json');
            if (! $manifestStat || $manifestStat['size'] > 8388608 || $zip->numFiles > 100000) {
                throw new \RuntimeException('Manifest atau jumlah berkas melewati batas.');
            }
            $manifest = json_decode($zip->getFromName('SILPJ-V3-BACKUP.json') ?: '', true);
            if (($manifest['product'] ?? null) !== 'silpj-v3' || ($manifest['schema'] ?? null) !== 3 || empty($manifest['sha256']['database/silpj-v3.sqlite'])) {
                throw new \RuntimeException('Cadangan harus format SILPJ V3 schema 3 dengan checksum.');
            }
            $seen = [];
            $size = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                $size += $stat['size'];
                if ($size > 5 * 1024 * 1024 * 1024 || isset($seen[$name]) || str_contains($name, '..') || str_contains($name, '\\') || str_starts_with($name, '/')) {
                    throw new \RuntimeException('Isi arsip tidak aman atau terlalu besar.');
                }
                $seen[$name] = true;
                if ($name === 'SILPJ-V3-BACKUP.json') {
                    continue;
                }
                if ($name !== 'database/silpj-v3.sqlite' && ! preg_match('#^storage/app/private/v3/[a-zA-Z0-9_./-]+$#D', $name)) {
                    throw new \RuntimeException('Lokasi arsip tidak diizinkan.');
                }
                $stream = $zip->getStream($name);
                if (! $stream) {
                    throw new \RuntimeException('Berkas arsip tidak dapat dibaca.');
                }
                try {
                    $hash = hash_init('sha256');
                    hash_update_stream($hash, $stream);
                    $actual = hash_final($hash);
                } finally {
                    fclose($stream);
                }
                if (! hash_equals($manifest['sha256'][$name] ?? '', $actual)) {
                    throw new \RuntimeException('Checksum cadangan tidak cocok.');
                }
            }
            foreach ($manifest['sha256'] as $name => $hash) {
                if (! isset($seen[$name])) {
                    throw new \RuntimeException('Cadangan tidak lengkap.');
                }
            }

            return $manifest;
        } finally {
            $zip->close();
        }
    }

    public function restore(string $path): void
    {
        if (! app()->isDownForMaintenance()) {
            throw new \RuntimeException('Aktifkan maintenance dan hentikan web server serta worker sebelum pemulihan.');
        }
        $this->validate($path);
        $stage = storage_path('app/private/restore-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($stage, 0700);
        $zip = new \ZipArchive;
        $zip->open($path);
        try {
            if (! $zip->extractTo($stage)) {
                throw new \RuntimeException('Ekstraksi gagal.');
            }
            $zip->close();
            $source = $stage.'/database/silpj-v3.sqlite';
            $pdo = new \PDO('sqlite:'.$source);
            if ($pdo->query('PRAGMA integrity_check')->fetchColumn() !== 'ok' || $pdo->query('PRAGMA foreign_key_check')->fetch() !== false || $pdo->query('SELECT product FROM v3_identity WHERE id=1')->fetchColumn() !== 'silpj-v3') {
                throw new \RuntimeException('Integritas database cadangan gagal.');
            }
            $expected = DB::table('migrations')->orderBy('migration')->pluck('migration')->all();
            $actual = $pdo->query('SELECT migration FROM migrations ORDER BY migration')->fetchAll(\PDO::FETCH_COLUMN);
            if ($actual !== $expected) {
                throw new \RuntimeException('Versi migrasi cadangan berbeda. Gunakan kode yang sesuai.');
            }
            foreach ($pdo->query('SELECT path,sha256 FROM v3_attachments') as $row) {
                if (! preg_match('#^v3/[a-zA-Z0-9_./-]+$#D', $row['path']) || str_contains($row['path'], '..')) {
                    throw new \RuntimeException('Referensi lampiran tidak aman.');
                }
                $file = $stage.'/storage/app/private/'.$row['path'];
                if (! is_file($file) || hash_file('sha256', $file) !== $row['sha256']) {
                    throw new \RuntimeException('Lampiran cadangan tidak lengkap.');
                }
            }
            $pdo = null;
            $this->create(); // Recovery copy of the current system, before replacement.
            $db = config('database.connections.silpj_v3.database');
            DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
            DB::disconnect();
            $assets = Storage::disk('local')->path('v3');
            $incoming = $stage.'/storage/app/private/v3';
            File::ensureDirectoryExists($incoming);
            $old = $assets.'.previous-'.bin2hex(random_bytes(6));
            $oldDb = $db.'.previous-'.bin2hex(random_bytes(6));
            $nextDb = $db.'.incoming-'.bin2hex(random_bytes(6));
            if (! copy($source, $nextDb) || hash_file('sha256', $source) !== hash_file('sha256', $nextDb)) {
                @unlink($nextDb);
                throw new \RuntimeException('Gagal menyiapkan database pemulihan.');
            }
            $hadAssets = is_dir($assets);
            $movedAssets = false;
            $movedDb = false;
            try {
                if ($hadAssets && ! rename($assets, $old)) {
                    throw new \RuntimeException('Gagal menyiapkan aset lama.');
                }
                $movedAssets = true;
                if (! rename($incoming, $assets)) {
                    throw new \RuntimeException('Gagal memasang aset cadangan.');
                }
                if (! rename($db, $oldDb)) {
                    throw new \RuntimeException('Gagal menyiapkan database lama.');
                }
                $movedDb = true;
                foreach (['-wal', '-shm'] as $suffix) {
                    if (is_file($db.$suffix)) {
                        unlink($db.$suffix);
                    }
                }
                if (! rename($nextDb, $db)) {
                    throw new \RuntimeException('Gagal memasang database.');
                }
            } catch (\Throwable $e) {
                if ($movedDb) {
                    if (is_file($db)) {
                        unlink($db);
                    }
                    rename($oldDb, $db);
                }
                if ($movedAssets) {
                    File::deleteDirectory($assets);
                    if ($hadAssets) {
                        rename($old, $assets);
                    }
                }
                throw $e;
            } finally {
                if (is_file($nextDb)) {
                    unlink($nextDb);
                }
            }
            if ($hadAssets) {
                File::deleteDirectory($old);
            }
            if (is_file($oldDb)) {
                unlink($oldDb);
            }
        } finally {
            File::deleteDirectory($stage);
        }
    }

    public function prune(int $keep): void
    {
        $files = glob(storage_path('app/private/v3-backups/*.zip')) ?: [];
        rsort($files);
        foreach (array_slice($files, max(1, $keep)) as $file) {
            unlink($file);
        }
    }
}
