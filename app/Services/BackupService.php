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
                $password=config('silpj.backup_password');
                if($password && !$zip->setPassword($password)) throw new \RuntimeException('Gagal mengatur enkripsi cadangan.');
                $files = ['database/silpj-v3.sqlite' => $db];
                foreach (Storage::disk('local')->allFiles('v3') as $file) {
                    $files['storage/app/private/'.$file] = Storage::disk('local')->path($file);
                }
                $hashes = [];
                foreach ($files as $entry => $source) {
                    if (! $zip->addFile($source, $entry)) {
                        throw new \RuntimeException('Gagal menambahkan berkas cadangan.');
                    }
                    if($password && !$zip->setEncryptionName($entry, \ZipArchive::EM_AES_256)) throw new \RuntimeException('Enkripsi ZIP tidak didukung.');
                    $hashes[$entry] = hash_file('sha256', $source);
                }
                $zip->addFromString('SILPJ-V3-BACKUP.json', json_encode(['product' => 'silpj-v3', 'schema' => 3, 'created_at' => now()->toIso8601String(), 'sha256' => $hashes], JSON_THROW_ON_ERROR));
                if($password && !$zip->setEncryptionName('SILPJ-V3-BACKUP.json', \ZipArchive::EM_AES_256)) throw new \RuntimeException('Enkripsi manifest gagal.');
                if (! $zip->close()) {
                    throw new \RuntimeException('Gagal menyelesaikan ZIP.');
                }
                $opened = false;
                $this->validate($path);

                if($destination=config('silpj.backup_copy_directory')) {
                    File::ensureDirectoryExists($destination,0700);
                    $target=rtrim($destination,'/\\').'/'.basename($path);
                    if(realpath($destination)!==realpath($dir) && (!copy($path,$target) || hash_file('sha256',$target)!==hash_file('sha256',$path))) throw new \RuntimeException('Salinan cadangan eksternal gagal.');
                }
                File::put(storage_path('app/private/backup-status.json'),json_encode(['ok'=>true,'at'=>now()->toIso8601String(),'file'=>basename($path),'encrypted'=>(bool)$password]));
                return $path;
            } catch (\Throwable $e) {
                File::put(storage_path('app/private/backup-status.json'),json_encode(['ok'=>false,'at'=>now()->toIso8601String(),'message'=>'Pencadangan gagal. Periksa log server dan tujuan salinan.']));
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
        if($password=config('silpj.backup_password')) $zip->setPassword($password);
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
                $zip->getExternalAttributesIndex($i,$opsys,$attributes);
                if((($attributes >> 16) & 0170000) === 0120000) throw new \RuntimeException('Tautan simbolik tidak diizinkan dalam cadangan.');
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
        if($password=config('silpj.backup_password')) $zip->setPassword($password);
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
            $expected = array_map(fn($p)=>pathinfo($p,PATHINFO_FILENAME),glob(database_path('migrations/*.php'))); sort($expected);
            $actual = $pdo->query('SELECT migration FROM migrations ORDER BY migration')->fetchAll(\PDO::FETCH_COLUMN);
            if (!$actual || $actual !== array_slice($expected,0,count($actual))) {
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
            $assetPaths=[];
            foreach($pdo->query('SELECT logo_pemda,logo_sekolah FROM school_profiles') as $row) foreach(['logo_pemda','logo_sekolah'] as $key) if($row[$key]) $assetPaths[]=$row[$key];
            foreach($pdo->query('SELECT snapshot FROM v3_documents') as $row) {
                $snapshot=json_decode($row['snapshot'],true,512,JSON_THROW_ON_ERROR);
                foreach(['logo_pemda_asset','logo_sekolah_asset'] as $key) if(!empty($snapshot['school'][$key])) $assetPaths[]=$snapshot['school'][$key];
            }
            foreach(array_unique($assetPaths) as $asset) {
                if(!preg_match('#^v3/[a-zA-Z0-9_./-]+$#D',$asset) || str_contains($asset,'..') || !is_file($stage.'/storage/app/private/'.$asset)) throw new \RuntimeException('Aset sekolah atau dokumen tidak lengkap.');
                if(str_contains($asset,'/document-assets/') && hash_file('sha256',$stage.'/storage/app/private/'.$asset)!==pathinfo($asset,PATHINFO_FILENAME)) throw new \RuntimeException('Checksum aset dokumen tidak cocok.');
            }
            $pdo = null;
            // Emergency recovery preserves raw damaged files without requiring SQL access.
            if(defined('SILPJ_EMERGENCY_RESTORE') && SILPJ_EMERGENCY_RESTORE) {
                $recovery=storage_path('app/private/emergency-original-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)));
                File::ensureDirectoryExists($recovery,0700);
                $oldPath=config('database.connections.silpj_v3.database');
                foreach(['','-wal','-shm'] as $suffix) if(is_file($oldPath.$suffix) && !copy($oldPath.$suffix,$recovery.'/database.sqlite'.$suffix)) throw new \RuntimeException('Gagal mengamankan berkas asli.');
            } else $this->create();
            $db = config('database.connections.silpj_v3.database');
            if(!(defined('SILPJ_EMERGENCY_RESTORE') && SILPJ_EMERGENCY_RESTORE)) DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
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
            $hadDb = is_file($db);
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
                if ($hadDb && ! rename($db, $oldDb)) {
                    throw new \RuntimeException('Gagal menyiapkan database lama.');
                }
                $movedDb = true;
                foreach (['-wal', '-shm'] as $suffix) {
                    if (is_file($db.$suffix)) {
                        rename($db.$suffix,$oldDb.$suffix);
                    }
                }
                if (! rename($nextDb, $db)) {
                    throw new \RuntimeException('Gagal memasang database.');
                }
                DB::purge();
                if(\Illuminate\Support\Facades\Artisan::call('migrate',['--force'=>true])!==0) throw new \RuntimeException('Migrasi setelah pemulihan gagal.');
                DB::table('users')->increment('session_version');
                DB::table('users')->update(['remember_token'=>null]);
            } catch (\Throwable $e) {
                DB::disconnect();
                foreach(['-wal','-shm'] as $suffix) if(is_file($db.$suffix)) unlink($db.$suffix);
                if ($movedDb) {
                    if (is_file($db)) {
                        unlink($db);
                    }
                    if($hadDb) rename($oldDb, $db);
                    foreach(['-wal','-shm'] as $suffix) if(is_file($oldDb.$suffix)) rename($oldDb.$suffix,$db.$suffix);
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
            foreach(['-wal','-shm'] as $suffix) if(is_file($oldDb.$suffix)) unlink($oldDb.$suffix);
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
