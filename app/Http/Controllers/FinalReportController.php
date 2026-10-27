<?php
namespace App\Http\Controllers;
use App\Models\{SchoolProfile,User};
use App\Models\V3\{Budget,Purchase};
use App\Services\{ReportQuery,V3Workflow};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class FinalReportController extends SchoolController {
 public function download(Request $r) {
  // Hold transaction locks while capturing the approved revisions and evidence.
  if($r->isMethod('post')) abort_unless($r->user()->isBendahara() || $r->user()->isKepalaSekolah(),403);
  $archiveId=$r->isMethod('post')?(string)\Illuminate\Support\Str::uuid():null;
  try { return DB::transaction(fn()=> $this->build($r,$archiveId)); }
  catch(\Throwable $e) {
   if($archiveId) \Illuminate\Support\Facades\Storage::disk('local')->deleteDirectory('v3/schools/'.$this->sid().'/lpj-archives/'.$archiveId);
   throw $e;
  }
 }
 public function archived(string $id, string $format) {
  abort_unless(in_array($format,['pdf','zip'],true),404);
  $archive=DB::table('v3_report_archives')->where('school_profile_id',$this->sid())->find($id);
  abort_unless($archive,404);
  $path=$format==='pdf'?$archive->pdf_path:$archive->zip_path;
  $expected=$format==='pdf'?$archive->pdf_sha256:$archive->zip_sha256;
  $disk=\Illuminate\Support\Facades\Storage::disk('local');
  abort_unless($disk->exists($path),404,'Berkas arsip tidak tersedia. Pulihkan dari cadangan.');
  abort_unless(hash_equals($expected,hash_file('sha256',$disk->path($path))),409,'Integritas arsip gagal. Pulihkan dari cadangan.');
  return $disk->download($path,'LPJ-'.$archive->tahun.'-'.$archive->id.'.'.$format,['X-Content-Type-Options'=>'nosniff']);
 }
 private function build(Request $r, ?string $archiveId) {
  $r->validate(['tahun'=>'required|integer|between:2000,2100']);
  $filters=ReportQuery::filters($r); $filters['status']='disetujui';
  $query=ReportQuery::query($this->sid(),$filters)->with(['documents','items','components']);
  if((clone $query)->count()>150) throw ValidationException::withMessages(['laporan'=>'Maksimal 150 transaksi per PDF. Gunakan filter bulan/kegiatan/sumber dana.']);
  $rows=$query->orderBy('tanggal')->orderBy('id')->lockForUpdate()->get();
  if($rows->isEmpty()) throw ValidationException::withMessages(['laporan'=>'Tidak ada transaksi disetujui sesuai filter.']);
  $attachments=DB::table('v3_attachments')->whereIn('purchase_id',$rows->pluck('id'))->where('is_active',true)->get()->groupBy('purchase_id');
  $total=['bruto'=>'0.00','netto'=>'0.00','potongan'=>'0.00']; $documents=collect(); $evidence=collect();
  foreach($rows as $p) {
   if($missing=\App\Services\LpjReadiness::missing($p)) throw ValidationException::withMessages(['laporan'=>$p->nomor_bukti.': '.implode(' ',$missing)]);
   foreach(['nopes','bapb','kuitansi'] as $kind) {
    $doc=$p->documents->where('jenis',$kind)->where('status','terbit')->where('revision',$p->revision)->first();
    if(!$doc) throw ValidationException::withMessages(['laporan'=>'Transaksi '.$p->nomor_bukti.' belum memiliki dokumen aktif lengkap.']);
    $documents->push($doc);
   }
   $files=($attachments[$p->id]??collect())->where('revision',$p->revision);
   if($files->isEmpty()) throw ValidationException::withMessages(['laporan'=>'Bukti revisi terbaru belum tersedia: '.$p->nomor_bukti]);
   foreach($files as $f) $evidence->push(['nomor'=>$p->nomor_bukti,'name'=>$f->name,'sha256'=>$f->sha256]);
   foreach($total as $key=>$v)$total[$key]=bcadd($v,(string)$p->$key,2);
  }
  $budgets=Budget::where('school_profile_id',$this->sid())->where('tahun',$filters['tahun'])
   ->when($filters['sumber_dana']??null,fn($q,$v)=>$q->where('sumber_dana',$v))
   ->when($filters['activity_id']??null,fn($q,$v)=>$q->whereIn('id',DB::table('v3_activities')->where('id',$v)->where('school_profile_id',$this->sid())->select('budget_id')))->get();
  $budgetTotal=$budgets->reduce(fn($s,$b)=>bcadd($s,(string)$b->nilai,2),'0.00');
  $school=SchoolProfile::findOrFail($this->sid()); $generatedAt=now();
  $reviewers=User::whereIn('id',$rows->pluck('approved_by')->filter())->pluck('name','id');
  V3Workflow::event('unduh_lpj_final',null,null,['filters'=>$filters,'purchases'=>$rows->pluck('id')->all(),'total'=>$total]);
  $pdf=Pdf::loadView('v3.print-final',compact('school','filters','rows','total','documents','evidence','budgets','budgetTotal','generatedAt','reviewers','archiveId'))->setPaper('a4')->output();
  if($archiveId) {
   \App\Services\LpjArchive::save($archiveId,$this->sid(),$filters,$rows,$attachments,$pdf);
   return redirect()->route('v3.reports',['tahun'=>$filters['tahun']])->with('success','LPJ telah diarsipkan. Unduh PDF atau paket bukti pada daftar arsip.');
  }
  return response($pdf,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="LPJ-final-'.$filters['tahun'].'.pdf"']);
 }
}
