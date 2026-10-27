<?php
namespace App\Services;
use App\Models\V3\Purchase;
use Illuminate\Support\Facades\DB;
class LpjReadiness {
    public static function validFile(object $file): bool {
        $disk=\Illuminate\Support\Facades\Storage::disk('local');
        if (!$disk->exists($file->path)) return false;
        $actual=hash_file('sha256',$disk->path($file->path));
        return is_string($actual) && hash_equals((string)$file->sha256,$actual);
    }
    public const CATEGORIES = ['nota'=>'Nota / invoice', 'pembayaran'=>'Bukti pembayaran', 'penerimaan'=>'Dokumentasi penerimaan', 'pajak'=>'Dokumen pajak', 'lainnya'=>'Pendukung lainnya'];
    public static function evidence(Purchase $p, string $category): bool {
        return DB::table('v3_attachments')->where('purchase_id',$p->id)->where('revision',$p->revision)->where('is_active',true)->where('category',$category)->get()->contains(fn($file)=>self::validFile($file));
    }
    public static function damaged(?\App\Models\V3\Document $doc): bool {
        foreach (($doc?->snapshot['inspection'] ?? []) as $row) {
            if (bccomp((string)($row['rusak']??0),'0',3)>0) return true;
        }
        return false;
    }
    public static function missing(Purchase $p): array {
        $missing=[];
        foreach (DB::table('v3_attachments')->where('purchase_id',$p->id)->where('revision',$p->revision)->where('is_active',true)->get() as $file) {
            if (!self::validFile($file)) $missing[]='Bukti hilang, berubah, atau rusak: '.$file->name.'. Unggah ulang atau arsipkan bukti tersebut.';
        }
        foreach(['nopes'=>'Nota pesanan','bapb'=>'Berita acara','kuitansi'=>'Kuitansi'] as $kind=>$label) {
            if(!V3Workflow::current($p,$kind)) $missing[]=$label.' revisi terbaru belum terbit.';
        }
        foreach(['nota','pembayaran'] as $category) {
            if(!self::evidence($p,$category)) $missing[]=self::CATEGORIES[$category].' revisi terbaru belum tersedia.';
        }
        if(self::damaged(V3Workflow::current($p,'bapb'))) $missing[]='Hasil pemeriksaan masih memuat barang rusak / pekerjaan belum sesuai. Koreksi penerimaan dan terbitkan ulang berita acara setelah diselesaikan.';
        $receipt=V3Workflow::current($p,'kuitansi');
        if($receipt && empty($receipt->snapshot['payment_confirmed'])) $missing[]='Kuitansi lama belum memiliki konfirmasi pembayaran; batalkan dan terbitkan ulang.';
        return $missing;
    }
}
