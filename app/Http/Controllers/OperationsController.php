<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\V3\{Activity,Vendor};
use App\Services\V3Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class OperationsController extends SchoolController {
 public function evidence(Request $r,int $id) {
  $this->editor(); $d=$r->validate(['action'=>'required|in:confirm,archive','reason'=>'required|string|min:5|max:2000','category'=>'required|in:nota,pembayaran,penerimaan,pajak,lainnya']);
  DB::transaction(function()use($d,$id){
   $f=DB::table('v3_attachments')->find($id); abort_unless($f,404);
   $p=V3Workflow::purchase($f->purchase_id,true); V3Workflow::assertEditable($p);
   DB::table('v3_attachments')->where('id',$id)->update(['category'=>$d['category'],'revision'=>$p->revision,'is_active'=>$d['action']==='confirm','updated_at'=>now()]);
   V3Workflow::event('bukti_'.$d['action'],$p,$d['reason'],['attachment_id'=>$id,'previous_revision'=>$f->revision,'revision'=>$p->revision,'previous_category'=>$f->category,'category'=>$d['category']]);
  },3); return back()->with('success','Status bukti diperbarui.');
 }
 public function cancelReceipt(Request $r,int $id) {
  $this->editor(); $d=$r->validate(['reason'=>'required|string|min:5|max:2000']);
  DB::transaction(function()use($d,$id){
   $batch=DB::table('v3_receipts')->find($id); abort_unless($batch,404);
   $p=V3Workflow::purchase($batch->purchase_id,true); V3Workflow::assertEditable($p);
   abort_unless(!$batch->cancelled_at && (int)$batch->revision===(int)$p->revision && (int)$batch->nopes_id===(int)V3Workflow::current($p,'nopes')?->id,422);
   if($b=V3Workflow::current($p,'bapb')) app(V3Workflow::class)->cancelDocument($b->id,$d['reason']);
   DB::table('v3_receipts')->where('id',$id)->update(['cancelled_at'=>now(),'cancel_reason'=>$d['reason'],'updated_at'=>now()]);
   V3Workflow::event('batalkan_penerimaan',$p,$d['reason'],['receipt_id'=>$id]);
  },3); return back()->with('success','Tahap dibatalkan. Catat tahap pengganti; terbitkan kembali berita acara/kuitansi bila terdampak.');
 }
 public function activity(Request $r,int $id) {
  $this->editor(); $d=$r->validate(['nama'=>'required|string|max:255','keterangan'=>'nullable|string|max:2000','is_archived'=>'required|boolean','reason'=>'required|string|min:5|max:2000']);
  DB::transaction(function()use($d,$id){
   $a=Activity::where('school_profile_id',$this->sid())->lockForUpdate()->findOrFail($id);
   if($a->purchases()->exists() && ($a->nama!==$d['nama'] || ($a->keterangan??'')!==($d['keterangan']??''))) throw ValidationException::withMessages(['nama'=>'Identitas kegiatan yang sudah memiliki transaksi dipertahankan untuk arsip. Gunakan kegiatan baru untuk identitas berbeda.']);
   $before=$a->toArray(); $a->update(collect($d)->except('reason')->all());
   V3Workflow::event('ubah_kegiatan',null,$d['reason'],['before'=>$before,'after'=>$a->fresh()->toArray()]);
  },3); return back()->with('success','Kegiatan diperbarui.');
 }
 public function audit(Request $r) {
  $d=$r->validate(['from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d','actor'=>'nullable|integer','action'=>'nullable|string|max:100','csv'=>'nullable|boolean']);
  $q=DB::table('v3_events as e')->join('users as u','u.id','=','e.actor_id')->where('e.school_profile_id',$this->sid())->select('e.*','u.name');
  if(!empty($d['from']))$q->whereDate('e.created_at','>=',$d['from']); if(!empty($d['to']))$q->whereDate('e.created_at','<=',$d['to']);
  if(!empty($d['actor']))$q->where('e.actor_id',$d['actor']); if(!empty($d['action']))$q->where('e.action',$d['action']);
  if($r->boolean('csv')) return response()->streamDownload(function()use($q){$f=fopen('php://output','w'); fwrite($f,"\xEF\xBB\xBF"); fputcsv($f,['Waktu','Pelaku','Aksi','Alasan'], ';'); foreach($q->orderBy('e.id')->cursor() as $e){$row=[$e->created_at,$e->name,$e->action,$e->reason];fputcsv($f,array_map(fn($v)=>preg_match('/^[=+@\-\t\r]/u',(string)$v)?"'".$v:$v,$row),';');}fclose($f);},'audit.csv');
  return view('v3.audit',['events'=>$q->orderByDesc('e.id')->paginate(50)->withQueryString(),'users'=>User::where('school_profile_id',$this->sid())->get()]);
 }
 public function notices() {return view('v3.notices',['notices'=>DB::table('v3_notices')->where('user_id',auth()->id())->where('school_profile_id',$this->sid())->orderByDesc('id')->paginate(30)]);}
 public function readNotice(int $id) { $n=DB::table('v3_notices')->where('user_id',auth()->id())->where('school_profile_id',$this->sid())->find($id);abort_unless($n,404);DB::table('v3_notices')->where('id',$id)->update(['read_at'=>now()]);return redirect()->route('v3.purchase',$n->purchase_id); }
 public function vendors(Request $r) { $d=$r->validate(['q'=>'nullable|string|max:100']); return view('v3.vendors',['vendors'=>Vendor::where('school_profile_id',$this->sid())->when($d['q']??null,fn($q,$v)=>$q->where('nama','like','%'.$v.'%'))->orderByDesc('id')->paginate(30)->withQueryString()]); }
}
