<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->isSystemAdmin(), 403);
        $files = glob(storage_path('app/private/v3-backups/*.zip')) ?: [];
        rsort($files);
        $backups = array_map(fn ($file) => ['name' => basename($file), 'size' => filesize($file), 'time' => date('Y-m-d H:i:s', filemtime($file))], array_slice($files, 0, 30));
        $disk = Storage::disk('local');
        $assetBytes = 0;
        $assetCount = 0;
        foreach ($disk->allFiles('v3') as $file) {
            $assetCount++;
            $assetBytes += $disk->size($file);
        }
        $db = config('database.connections.silpj_v3.database');
        $databaseBytes = is_file($db) ? filesize($db) : 0;

        $statusPath=storage_path('app/private/backup-status.json');
        view()->share('backupStatus', is_file($statusPath)?json_decode(file_get_contents($statusPath),true):null);
        return view('system.backups', compact('backups', 'assetBytes', 'assetCount', 'databaseBytes'));
    }

    public function checkRestore(Request $r, BackupService $service)
    {
        abort_unless($r->user()->isSystemAdmin(), 403);
        $r->validate(['password' => 'required|current_password', 'file' => 'required|file|mimes:zip|max:102400']);
        $path = $r->file('file')->store('v3-restore-pending', 'local');
        $disk = Storage::disk('local');
        try {
            $service->validate($disk->path($path));
            DB::table('system_events')->insert(['actor_id' => $r->user()->id, 'action' => 'validasi_cadangan_pemulihan', 'details' => json_encode(['file' => basename($path)]), 'created_at' => now(), 'updated_at' => now()]);

            return back()->with('success', 'Struktur dan checksum cadangan valid. Database serta kelengkapan lampiran akan diperiksa kembali saat pemulihan konsol.')->with('restore_file', $disk->path($path));
        } catch (\Throwable $e) {
            $disk->delete($path);
            report($e);

            return back()->withErrors(['file' => 'Cadangan tidak valid. Gunakan ZIP SILPJ schema 3 yang utuh.']);
        }
    }

    public function backup(Request $r, BackupService $service)
    {
        abort_unless($r->user()->isSystemAdmin(), 403);
        $r->validate(['password' => 'required|current_password']);
        $path = $service->create();
        DB::table('system_events')->insert(['actor_id' => $r->user()->id, 'action' => 'unduh_cadangan_sistem', 'created_at' => now(), 'updated_at' => now()]);

        return response()->download($path, 'cadangan-silpj-v3-'.date('Ymd-His').'.zip');
    }
}
