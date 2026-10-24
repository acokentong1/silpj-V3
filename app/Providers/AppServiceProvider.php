<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Laravel merges built-in database connections before providers run.
        // SILPJ deliberately permits only its dedicated database connection.
        $connection = config('database.connections.silpj_v3');
        if (! is_array($connection) || ($connection['driver'] ?? null) !== 'sqlite') {
            throw new \RuntimeException('Konfigurasi koneksi silpj_v3 SQLite tidak ditemukan.');
        }
        config(['database.default' => 'silpj_v3', 'database.connections' => ['silpj_v3' => $connection]]);
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        if (! app()->environment('testing')) {
            $path = database_path('silpj-v3.sqlite');
            if (is_link($path) || (file_exists($path) && realpath($path) !== realpath(database_path()).DIRECTORY_SEPARATOR.'silpj-v3.sqlite')) {
                throw new \RuntimeException('Database V3 harus berupa berkas lokal khusus, bukan tautan.');
            }
            if (file_exists($path) && filesize($path) > 0) {
                $pdo = new \PDO('sqlite:'.$path);
                $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
                if ($tables && ! in_array('v3_identity', $tables)) {
                    throw new \RuntimeException('Berkas ini bukan database SILPJ V3. Operasi dihentikan.');
                }
                if (in_array('v3_identity', $tables) && $pdo->query('SELECT product FROM v3_identity WHERE id=1')->fetchColumn() !== 'silpj-v3') {
                    throw new \RuntimeException('Identitas database V3 tidak sesuai.');
                }
            }
        }
    }
}
