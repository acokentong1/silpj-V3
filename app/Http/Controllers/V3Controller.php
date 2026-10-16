<?php
namespace App\Http\Controllers;
use App\Models\V3\{Purchase,Activity,Vendor,Document,Budget};
use App\Models\{SchoolProfile,User};
use App\Services\V3Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage,Hash};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class V3Controller extends Controller {
 private function sid(){return auth()->user()->school_profile_id??abort(403,'Akun belum terhubung ke sekolah.');}
 private function editor(){abort_unless(auth()->user()->isBendahara(),403);}
 public function index(Request $r){
  if($r->user()->role==='kepala_sekolah')return redirect()->route('v3.approvals');
  $years=Budget::where('school_profile_id',$this->sid())->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($year)=>(int)$year);
  if($years->isEmpty())$years=collect([(int)date('Y')]);
  $yearKey='dashboard_year_'.$this->sid();
  $defaultYear=$years->contains((int)date('Y'))?(int)date('Y'):$years->first();
  if($r->has('tahun')){
   $r->validate(['tahun'=>['required','integer',Rule::in($years->all())]]);
   $selectedYear=(int)$r->input('tahun');
  }else{$selectedYear=(int)$r->session()->get($yearKey,$defaultYear);if(!$years->contains($selectedYear))$selectedYear=$defaultYear;}
  $r->session()->put($yearKey,$selectedYear);

  $q=Activity::where('school_profile_id',$this->sid())->whereHas('budget',fn($q)=>$q->where('tahun',$selectedYear))->with('budget')->withCount('purchases')->withSum(['purchases'=>fn($q)=>$q->where('status','!=','dibatalkan')],'bruto');
  if($r->filled('q'))$q->where('nama','like','%'.mb_substr($r->input('q'),0,100).'%');
  $budgets=Budget::where('school_profile_id',$this->sid())->where('tahun',$selectedYear)->orderByDesc('tahun')->orderBy('sumber_dana')->get();
  $usage=DB::table('v3_purchases as p')->join('v3_activities as a','a.id','=','p.activity_id')
   ->where('p.school_profile_id',$this->sid())->where('a.school_profile_id',$this->sid())
   ->whereIn('a.budget_id',$budgets->pluck('id'))->where('p.status','!=','dibatalkan')->select('a.budget_id','p.bruto')->get()->groupBy('budget_id');
  $budgetSummary=['total'=>'0.00','used'=>'0.00'];
  foreach($budgets as $budget){
   $used=($usage->get($budget->id)??collect())->reduce(fn($sum,$row)=>bcadd($sum,(string)$row->bruto,2),'0.00');
   $budget->used=$used;$budget->remaining=bcsub((string)$budget->nilai,$used,2);
   $budget->percentage=bccomp((string)$budget->nilai,'0',2)>0?bcdiv(bcmul($used,'100',4),(string)$budget->nilai,2):null;
   $budgetSummary['total']=bcadd($budgetSummary['total'],(string)$budget->nilai,2);
   $budgetSummary['used']=bcadd($budgetSummary['used'],$used,2);
  }
  $budgetSummary['remaining']=bcsub($budgetSummary['total'],$budgetSummary['used'],2);
  $budgetSummary['percentage']=bccomp($budgetSummary['total'],'0',2)>0?bcdiv(bcmul($budgetSummary['used'],'100',4),$budgetSummary['total'],2):null;
  return view('v3.index',['activities'=>$q->latest()->paginate(15)->appends(['tahun'=>$selectedYear,'q'=>$r->input('q')]),'years'=>$years,'selectedYear'=>$selectedYear,'budgets'=>$budgets,'budgetSummary'=>$budgetSummary,'counts'=>Purchase::where('school_profile_id',$this->sid())->whereHas('activity.budget',fn($q)=>$q->where('tahun',$selectedYear))->selectRaw('status,count(*) as total')->groupBy('status')->pluck('total','status')]);
 }
 public function activityStore(Request $r){$this->editor();$d=$r->validate(['nama'=>'required|string|max:255','budget_id'=>['required',Rule::exists('v3_budgets','id')->where('school_profile_id',$this->sid())],'keterangan'=>'nullable|string|max:2000']);$a=DB::transaction(function()use($d){$a=Activity::create($d+['school_profile_id'=>$this->sid(),'created_by'=>auth()->id()]);V3Workflow::event('buat_kegiatan',null,null,['id'=>$a->id,'nama'=>$a->nama]);return $a;});return redirect()->route('v3.activity',$a->id)->with('success','Kegiatan dibuat. Tambahkan transaksi belanja.');}
 public function activity(int $id){$a=Activity::where('school_profile_id',$this->sid())->with('budget')->findOrFail($id);return view('v3.activity',['a'=>$a,'purchases'=>$a->purchases()->with('vendor')->latest()->get()]);}
 public function activityDocuments(int $id){
  $a=Activity::where('school_profile_id',$this->sid())->findOrFail($id);
  $documents=Document::query()
   ->join('v3_purchases','v3_purchases.id','=','v3_documents.purchase_id')
   ->where('v3_purchases.activity_id',$a->id)
   ->where('v3_purchases.school_profile_id',$this->sid())
   ->where('v3_documents.school_profile_id',$this->sid())
   ->where('v3_purchases.status','!=','dibatalkan')
   ->where('v3_documents.status','terbit')
   ->whereColumn('v3_documents.revision','v3_purchases.revision')
   ->whereIn('v3_documents.jenis',['nopes','bapb','kuitansi'])
   ->orderBy('v3_purchases.tanggal')->orderBy('v3_purchases.id')
   ->orderByRaw("CASE v3_documents.jenis WHEN 'nopes' THEN 1 WHEN 'bapb' THEN 2 WHEN 'kuitansi' THEN 3 END")
   ->select('v3_documents.*')->get();
  if($documents->isEmpty())return redirect()->route('v3.activity',$a->id)->withErrors(['dokumen'=>'Belum ada dokumen aktif yang dapat diunduh. Terbitkan Nopes, Berita Acara, atau Kuitansi pada transaksi kegiatan ini terlebih dahulu.']);
  return Pdf::loadView('v3.print-activity',compact('a','documents'))->setPaper('a4')->download('dokumen-kegiatan-'.$a->id.'.pdf');
 }
 public function form(int $activity,?int $id=null){$this->editor();$a=Activity::where('school_profile_id',$this->sid())->with('budget')->findOrFail($activity);$p=$id?V3Workflow::purchase($id):null;if($p){V3Workflow::assertEditable($p);abort_unless($p->activity_id===$a->id,404);$p->load('items','components','vendor');}return view('v3.form',compact('a','p'));}
 private function data(Request $r,bool $edit):array {
  $d=$r->validate(['nomor_bukti'=>'required|string|max:100','tanggal'=>'required|date_format:Y-m-d','kategori'=>'required|in:Barang,Jasa','vendor_nama'=>'required|string|max:255','vendor_alamat'=>'nullable|string|max:1000','vendor_penerima'=>'required|string|max:255','vendor_npwp'=>'nullable|string|max:50','reason'=>($edit?'required':'nullable').'|string|min:5|max:2000',
   'items'=>'required|array|min:1|max:200','items.*.nama'=>'required|string|max:255','items.*.volume'=>'required|numeric|min:0.001|max:1000000|decimal:0,3','items.*.satuan'=>'required|string|max:40','items.*.harga'=>'required|numeric|min:0|max:1000000000|decimal:0,2',
   'components'=>'nullable|array|max:30','components.*.label'=>'required|string|max:60','components.*.kategori'=>'required|in:pajak,lainnya','components.*.metode'=>'required|in:nominal,persentase','components.*.persentase'=>'nullable|numeric|min:0|max:100|decimal:0,4','components.*.dasar'=>'nullable|numeric|min:0|max:99999999999999|decimal:0,2','components.*.nominal'=>'nullable|numeric|min:0|max:99999999999999|decimal:0,2','components.*.perlakuan'=>'required|in:tambah,potong,termasuk']);
  foreach($d['components']??[] as $c){foreach($c['metode']==='persentase'?['persentase','dasar']:['nominal'] as $key)if(!isset($c[$key]))throw ValidationException::withMessages(['components'=>'Lengkapi nilai komponen sesuai metode yang dipilih.']);}
  return $d;
 }
 public function save(Request $r,int $activity,?int $id=null){$this->editor();$a=Activity::where('school_profile_id',$this->sid())->with('budget')->findOrFail($activity);$d=$this->data($r,(bool)$id);if((int)substr($d['tanggal'],0,4)!==$a->budget->tahun)throw ValidationException::withMessages(['tanggal'=>'Tanggal transaksi harus sesuai tahun anggaran.']);$p=app(V3Workflow::class)->save($a,$d,$id);return redirect()->route('v3.purchase',$p->id)->with('success','Transaksi dan seluruh rincian tersimpan.');}
 public function purchase(int $id){$p=V3Workflow::purchase($id)->load('activity.budget','items','components','vendor','documents');return view('v3.purchase',['p'=>$p,'attachments'=>DB::table('v3_attachments')->where('purchase_id',$id)->get(),'events'=>DB::table('v3_events')->join('users','users.id','=','v3_events.actor_id')->where('purchase_id',$id)->select('v3_events.*','users.name')->orderByDesc('v3_events.id')->get()]);}
 public function issue(Request $r,int $id){$this->editor();$d=$r->validate(['jenis'=>'required|in:nopes,bapb,kuitansi','tanggal'=>'required|date_format:Y-m-d','tanggal_lunas'=>'nullable|required_if:jenis,kuitansi|date_format:Y-m-d','catatan'=>'nullable|string|max:2000','inspection'=>'nullable|required_if:jenis,bapb|array|max:200','inspection.*.baik'=>'required|numeric|min:0|max:1000000|decimal:0,3','inspection.*.rusak'=>'required|numeric|min:0|max:1000000|decimal:0,3','inspection.*.catatan'=>'nullable|string|max:255']);$doc=app(V3Workflow::class)->issue($id,$d);return redirect()->route('v3.document',$doc->id);}
 public function document(Request $r,int $id){$doc=Document::where('school_profile_id',$this->sid())->findOrFail($id);$s=$doc->snapshot;$data=compact('doc','s');if($r->boolean('pdf'))return Pdf::loadView('v3.print-v1',$data)->setPaper('a4')->download($doc->jenis.'-'.$doc->id.'.pdf');return view('v3.print-v1',$data);}
 public function transition(Request $r,int $id){$d=$r->validate(['action'=>'required|in:ajukan,setujui,revisi,buka,batalkan','reason'=>'nullable|string|max:2000']);app(V3Workflow::class)->transition($id,$d['action'],$d['reason']??null);return back()->with('success','Status diperbarui.');}
 public function cancelDocument(Request $r,int $id){$d=$r->validate(['reason'=>'required|string|min:5|max:2000']);app(V3Workflow::class)->cancelDocument($id,$d['reason']);return back()->with('success','Dokumen dibatalkan; dokumen turunannya juga dibatalkan.');}
 public function upload(Request $r,int $id){$this->editor();$r->validate(['file'=>'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);$file=$r->file('file');$path=$file->store('v3/schools/'.$this->sid().'/bukti','local');try{DB::transaction(function()use($file,$path,$id){$p=V3Workflow::purchase($id,true);V3Workflow::assertEditable($p);DB::table('v3_attachments')->insert(['purchase_id'=>$id,'name'=>$file->getClientOriginalName(),'path'=>$path,'mime'=>$file->getMimeType(),'sha256'=>hash_file('sha256',Storage::disk('local')->path($path)),'created_at'=>now(),'updated_at'=>now()]);V3Workflow::event('unggah_bukti',$p);},3);}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}return back()->with('success','Bukti tersimpan.');}
 public function attachment(int $id){$f=DB::table('v3_attachments')->find($id);abort_unless($f,404);V3Workflow::purchase($f->purchase_id);return Storage::disk('local')->download($f->path,$f->name,['X-Content-Type-Options'=>'nosniff']);}
 public function settings(){return view('v3.settings',['s'=>SchoolProfile::findOrFail($this->sid())]);}
 public function settingsSave(Request $r){$this->editor();$rules=[];foreach(['nama_sekolah','nama_pemda','nama_dinas','tempat_cetak','kepala_sekolah','bendahara','pemeriksa','kode_surat','kode_sekolah'] as $f)$rules[$f]='required|string|max:255';foreach(['nip_kepala','nip_bendahara','nip_pemeriksa'] as $f)$rules[$f]='nullable|string|max:50';$rules+=['npsn'=>['required','digits:8',Rule::unique('school_profiles','npsn')->ignore($this->sid())],'alamat'=>'required|string|max:2000','logo_pemda'=>'nullable|image|mimes:png,jpg,jpeg|max:2048|dimensions:max_width=4000,max_height=4000','logo_sekolah'=>'nullable|image|mimes:png,jpg,jpeg|max:2048|dimensions:max_width=4000,max_height=4000'];$d=$r->validate($rules);$paths=[];foreach(['logo_pemda','logo_sekolah'] as $f){unset($d[$f]);if($r->hasFile($f)){$d[$f]=$r->file($f)->store('v3/schools/'.$this->sid().'/logos','local');$paths[]=$d[$f];}}try{DB::transaction(function()use($d){$s=SchoolProfile::whereKey($this->sid())->lockForUpdate()->firstOrFail();$before=$s->toArray();$s->update($d);V3Workflow::event('ubah_pengaturan',null,null,['before'=>$before,'after'=>$s->toArray()]);});}catch(\Throwable $e){Storage::disk('local')->delete($paths);throw $e;}return back()->with('success','Pengaturan tersimpan. Dokumen yang sudah terbit tetap memakai data saat diterbitkan.');}
 public function logo(string $kind){abort_unless(in_array($kind,['logo_pemda','logo_sekolah']),404);$s=SchoolProfile::findOrFail($this->sid());abort_unless($s->$kind && Storage::disk('local')->exists($s->$kind),404);return response()->file(Storage::disk('local')->path($s->$kind));}
 public function budgets(){return view('v3.budgets',['budgets'=>Budget::where('school_profile_id',$this->sid())->orderByDesc('tahun')->get()]);}
 public function budgetSave(Request $r){$this->editor();$d=$r->validate(['tahun'=>'required|integer|between:2000,2100','sumber_dana'=>'required|string|max:100','nilai'=>'required|numeric|min:0|max:99999999999999|decimal:0,2']);DB::transaction(function()use($d){SchoolProfile::whereKey($this->sid())->lockForUpdate()->firstOrFail();$b=Budget::firstOrNew(['school_profile_id'=>$this->sid(),'tahun'=>$d['tahun'],'sumber_dana'=>$d['sumber_dana']]);$used=$b->exists?Purchase::whereHas('activity',fn($q)=>$q->where('budget_id',$b->id))->where('status','!=','dibatalkan')->pluck('bruto')->reduce(fn($c,$v)=>bcadd($c,(string)$v,2),'0'):'0';if(bccomp((string)$d['nilai'],$used,2)<0)throw ValidationException::withMessages(['nilai'=>'Anggaran tidak boleh kurang dari belanja yang sudah dicadangkan.']);$before=$b->toArray();$b->nilai=$d['nilai'];$b->save();V3Workflow::event('simpan_anggaran',null,null,['before'=>$before,'after'=>$b->toArray()]);},3);return back()->with('success','Anggaran tersimpan.');}
 public function budgetEdit(int $id){
  $this->editor();
  $b=Budget::where('school_profile_id',$this->sid())->findOrFail($id);
  $hasPurchases=Purchase::whereHas('activity',fn($q)=>$q->where('budget_id',$b->id))->exists();
  return view('v3.budget-edit',compact('b','hasPurchases'));
 }
 public function budgetUpdate(Request $r,int $id){
  $this->editor();
  DB::transaction(function()use($r,$id){
   SchoolProfile::whereKey($this->sid())->lockForUpdate()->firstOrFail();
   $b=Budget::where('school_profile_id',$this->sid())->lockForUpdate()->findOrFail($id);
   $d=$r->validate(['tahun'=>'required|integer|between:2000,2100','sumber_dana'=>['required','string','max:100',Rule::unique('v3_budgets','sumber_dana')->where(fn($q)=>$q->where('school_profile_id',$this->sid())->where('tahun',$r->input('tahun')))->ignore($b->id)],'nilai'=>'required|numeric|min:0|max:99999999999999|decimal:0,2']);
   $purchases=Purchase::whereHas('activity',fn($q)=>$q->where('budget_id',$b->id));
   if((clone $purchases)->exists()&&((int)$d['tahun']!==(int)$b->tahun||$d['sumber_dana']!==$b->sumber_dana)){
    throw ValidationException::withMessages(['tahun'=>'Tahun dan sumber dana tidak dapat diubah karena anggaran sudah memiliki transaksi. Nilai anggaran tetap dapat diperbarui.']);
   }
   $used=$purchases->where('status','!=','dibatalkan')->pluck('bruto')->reduce(fn($c,$v)=>bcadd($c,(string)$v,2),'0');
   if(bccomp((string)$d['nilai'],$used,2)<0)throw ValidationException::withMessages(['nilai'=>'Anggaran tidak boleh kurang dari belanja yang sudah dicadangkan.']);
   $before=$b->toArray();$b->update($d);
   V3Workflow::event('ubah_anggaran',null,null,['before'=>$before,'after'=>$b->toArray()]);
  },3);
  return redirect()->route('v3.budgets')->with('success','Anggaran berhasil diperbarui.');
 }
 public function budgetDestroy(Request $r,int $id){
  $this->editor();
  DB::transaction(function()use($r,$id){
   SchoolProfile::whereKey($this->sid())->lockForUpdate()->firstOrFail();
   $b=Budget::where('school_profile_id',$this->sid())->lockForUpdate()->findOrFail($id);
   $d=$r->validate(['reason'=>'required|string|min:5|max:2000']);
   app(V3Workflow::class)->reason($d['reason']);
   if(Activity::where('budget_id',$b->id)->exists())throw ValidationException::withMessages(['anggaran'=>'Anggaran tidak dapat dihapus karena sudah digunakan oleh kegiatan.']);
   $before=$b->toArray();$b->delete();
   V3Workflow::event('hapus_anggaran',null,$d['reason'],['before'=>$before]);
  },3);
  return redirect()->route('v3.budgets')->with('success','Anggaran berhasil dihapus.');
 }
 public function accounts(){abort_unless(auth()->user()->isAdmin(),403);return view('v3.accounts',['users'=>User::where('school_profile_id',$this->sid())->where('is_system_admin',false)->get()]);}
 public function accountSave(Request $r){abort_unless(auth()->user()->isAdmin(),403);$d=$r->validate(['name'=>'required|string|max:255','email'=>'required|email|max:255|unique:users,email','password'=>'required|string|min:8|confirmed','role'=>'required|in:admin,bendahara,kepala_sekolah,user']);DB::transaction(function()use($d){$u=new User();$u->forceFill($d+['school_profile_id'=>$this->sid(),'is_active'=>true]);unset($u->password_confirmation);$u->save();V3Workflow::event('buat_akun',null,null,['id'=>$u->id,'role'=>$u->role]);});return back()->with('success','Akun dibuat.');}
 public function accountUpdate(Request $r,int $id){abort_unless(auth()->user()->isAdmin(),403);$d=$r->validate(['role'=>'required|in:admin,bendahara,kepala_sekolah,user','is_active'=>'required|boolean','password'=>'nullable|string|min:8|confirmed']);abort_if($id===auth()->id(),422,'Perubahan peran/status akun sendiri tidak diperbolehkan.');DB::transaction(function()use($d,$id){$u=User::where('school_profile_id',$this->sid())->where('is_system_admin',false)->lockForUpdate()->findOrFail($id);$u->role=$d['role'];$u->is_active=$d['is_active'];if(!empty($d['password']))$u->password=$d['password'];$u->save();V3Workflow::event('ubah_akun',null,null,['id'=>$id,'role'=>$u->role,'is_active'=>$u->is_active]);});return back()->with('success','Akun diperbarui.');}
 public function reports(){return view('v3.reports',['rows'=>Purchase::where('school_profile_id',$this->sid())->with('activity.budget','vendor')->latest()->paginate(50),'events'=>DB::table('v3_events')->join('users','users.id','=','v3_events.actor_id')->where('v3_events.school_profile_id',$this->sid())->select('v3_events.*','users.name')->orderByDesc('v3_events.id')->limit(100)->get()]);}
 public function excel(){return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\V3Export($this->sid()),'rekap-silpj-v3.xlsx');}
 public function csv(){return response()->streamDownload(function(){$f=fopen('php://output','w');fwrite($f,"\xEF\xBB\xBF");fputcsv($f,['Kegiatan','Tahun','Sumber dana','Nomor bukti','Tanggal','Rekanan','Subtotal','Bruto','Potongan','Netto','Status'],';');Purchase::where('school_profile_id',$this->sid())->with('activity.budget','vendor')->orderBy('id')->chunk(200,function($rows)use($f){foreach($rows as $p){$values=[$p->activity->nama,$p->tahun,$p->activity->budget->sumber_dana,$p->nomor_bukti,$p->tanggal,$p->vendor->nama,$p->subtotal,$p->bruto,$p->potongan,$p->netto,$p->status];$values=array_map(fn($v)=>preg_match('/^[=+@\-\t\r]/u',(string)$v)?"'".$v:$v,$values);fputcsv($f,$values,';');}});fclose($f);},'rekap-silpj-v3.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
 public function backup(Request $r){abort_unless(auth()->user()->isSystemAdmin(),403);$r->validate(['password'=>'required|current_password']);$dir=storage_path('app/private/v3-backups');if(!is_dir($dir))mkdir($dir,0700,true);$base=$dir.'/'.bin2hex(random_bytes(12));$db=$base.'.sqlite';$zipPath=$base.'.zip';DB::statement("VACUUM INTO '".str_replace("'","''",$db)."'");$zip=new \ZipArchive();if($zip->open($zipPath,\ZipArchive::CREATE)!==true)throw new \RuntimeException('Gagal membuat cadangan.');$zip->addFile($db,'database/silpj-v3.sqlite');$zip->addFromString('SILPJ-V3-BACKUP.json',json_encode(['product'=>'silpj-v3','created_at'=>now()->toIso8601String(),'schema'=>2]));foreach(Storage::disk('local')->allFiles('v3') as $file)$zip->addFile(Storage::disk('local')->path($file),'storage/app/private/'.$file);$zip->close();unlink($db);DB::table('system_events')->insert(['actor_id'=>auth()->id(),'action'=>'unduh_cadangan_sistem','created_at'=>now(),'updated_at'=>now()]);return response()->download($zipPath,'cadangan-silpj-v3-'.date('Ymd-His').'.zip')->deleteFileAfterSend();}
}
