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
  $r->validate(['tahun'=>'required|integer|between:2000,2100']);
  $filters=ReportQuery::filters($r); $filters['status']='disetujui';
  $query=ReportQuery::query($this->sid(),$filters)->with(['documents','items','components']);
  if((clone $query)->count()>150) throw ValidationException::withMessages(['laporan'=>'Maksimal 150 transaksi per PDF. Gunakan filter bulan/kegiatan/sumber dana.']);
  $rows=$query->orderBy('tanggal')->orderBy('id')->get();
  if($rows->isEmpty()) throw ValidationException::withMessages(['laporan'=>'Tidak ada transaksi disetujui sesuai filter.']);
  $attachments=DB::table('v3_attachments')->whereIn('purchase_id',$rows->pluck('id'))->where('is_active',true)->get()->groupBy('purchase_id');
  $total=['bruto'=>'0.00','netto'=>'0.00','potongan'=>'0.00']; $documents=collect(); $evidence=collect();
  foreach($rows as $p) {
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
  return Pdf::loadView('v3.print-final',compact('school','filters','rows','total','documents','evidence','budgets','budgetTotal','generatedAt','reviewers'))->setPaper('a4')->download('LPJ-final-'.$filters['tahun'].'.pdf');
 }
}
