<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class DocumentAssets
{
    public static function freeze(?string $path, int $school): ?string
    {
        $disk = Storage::disk('local');
        if (! $path || ! $disk->exists($path)) {
            return null;
        }
        $bytes = $disk->get($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return null;
        }
        $target = 'v3/schools/'.$school.'/document-assets/'.hash('sha256', $bytes).'.'.($mime === 'image/png' ? 'png' : 'jpg');
        if (! $disk->exists($target) && ! $disk->put($target, $bytes)) {
            throw new \RuntimeException('Gagal menyimpan aset dokumen.');
        }

        return $target;
    }

    public static function hydrate(array $snapshot, int $school): array
    {
        foreach (['logo_pemda', 'logo_sekolah'] as $key) {
            $path = $snapshot['school'][$key.'_asset'] ?? null;
            if (! $path) {
                continue;
            } // Legacy Base64 snapshots remain readable.
            if (! preg_match('#^v3/schools/'.preg_quote((string) $school, '#').'/document-assets/[a-f0-9]{64}\.(png|jpg)$#D', $path)) {
                throw new \RuntimeException('Referensi aset tidak valid.');
            }
            $bytes = Storage::disk('local')->get($path);
            if (hash('sha256', $bytes) !== pathinfo($path, PATHINFO_FILENAME)) {
                throw new \RuntimeException('Integritas aset dokumen gagal.');
            }
            $snapshot['school'][$key.'_data'] = 'data:'.(str_ends_with($path, '.png') ? 'image/png' : 'image/jpeg').';base64,'.base64_encode($bytes);
        }

        return $snapshot;
    }
}
