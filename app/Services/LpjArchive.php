<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class LpjArchive {
    public static function save(string $id, int $school, array $filters, $rows, $attachments, string $pdf): void {
        $disk=Storage::disk('local');
        $base='v3/schools/'.$school.'/lpj-archives/'.$id;
        $zip=new \ZipArchive;
        $opened=false;
        try {
            $disk->put($base.'/laporan.pdf',$pdf);
            if($zip->open($disk->path($base.'/paket.zip'), \ZipArchive::CREATE | \ZipArchive::EXCL)!==true) throw new \RuntimeException('Gagal membuat paket LPJ.');
            $opened=true;
            if(!$zip->addFile($disk->path($base.'/laporan.pdf'),'LPJ.pdf')) throw new \RuntimeException('Gagal memasukkan PDF.');
            $manifest=['archive_id'=>$id,'created_at'=>now()->toIso8601String(),'filters'=>$filters,'pdf_sha256'=>hash('sha256',$pdf),'transactions'=>[],'files'=>[]];
            $size=strlen($pdf);
            foreach($rows as $p) {
                $manifest['transactions'][]=['id'=>$p->id,'nomor_bukti'=>$p->nomor_bukti,'revision'=>$p->revision,'approved_by'=>$p->approved_by,'approved_at'=>$p->approved_at,'documents'=>$p->documents->where('status','terbit')->where('revision',$p->revision)->pluck('id')->values()->all()];
                foreach(($attachments[$p->id]??collect())->where('revision',$p->revision) as $f) {
                    if(!$disk->exists($f->path)) throw ValidationException::withMessages(['laporan'=>'Berkas bukti hilang: '.$f->name]);
                    $size+=$disk->size($f->path);
                    if($size>250*1024*1024) throw ValidationException::withMessages(['laporan'=>'Paket melebihi 250 MB. Gunakan filter bulan atau kegiatan.']);
                    if(!hash_equals($f->sha256,hash_file('sha256',$disk->path($f->path)))) throw ValidationException::withMessages(['laporan'=>'Integritas bukti gagal: '.$f->name]);
                    $extension=['application/pdf'=>'pdf','image/png'=>'png','image/jpeg'=>'jpg'][$f->mime]??'bin';
                    $entry='bukti/transaksi-'.$p->id.'/'.$f->id.'.'.$extension;
                    if(!$zip->addFile($disk->path($f->path),$entry)) throw new \RuntimeException('Gagal memasukkan bukti.');
                    $manifest['files'][]=['entry'=>$entry,'original_name'=>$f->name,'category'=>$f->category,'sha256'=>$f->sha256,'purchase_id'=>$p->id,'revision'=>$f->revision];
                }
            }
            if(!$zip->addFromString('manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))) throw new \RuntimeException('Gagal menyimpan daftar arsip.');
            if(!$zip->close()) throw new \RuntimeException('Gagal menyelesaikan ZIP LPJ.');
            $opened=false;
            DB::table('v3_report_archives')->insert(['id'=>$id,'school_profile_id'=>$school,'created_by'=>auth()->id(),'tahun'=>$filters['tahun'],'filters'=>json_encode($filters,JSON_THROW_ON_ERROR),'manifest'=>json_encode($manifest,JSON_THROW_ON_ERROR),'pdf_path'=>$base.'/laporan.pdf','zip_path'=>$base.'/paket.zip','pdf_sha256'=>hash('sha256',$pdf),'zip_sha256'=>hash_file('sha256',$disk->path($base.'/paket.zip')),'created_at'=>now(),'updated_at'=>now()]);
            V3Workflow::event('arsipkan_lpj',null,null,['archive_id'=>$id,'filters'=>$filters,'transactions'=>$manifest['transactions']]);
        } catch(\Throwable $e) {
            if($opened) $zip->close();
            $disk->deleteDirectory($base);
            throw $e;
        }
    }
}
