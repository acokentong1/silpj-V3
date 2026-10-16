<?php
namespace Tests\V3;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Http\UploadedFile;
use App\Models\{User,SchoolProfile};
use App\Models\V3\{Activity,Budget,Purchase,Document};
use App\Services\{V3Workflow,V3Calculator};

class WorkflowTest extends TestCase {
 use RefreshDatabase;
 private User $writer;private User $reviewer;private Activity $activity;
 protected function setUp():void {
  parent::setUp();Storage::fake('local');
  $s=SchoolProfile::create(['nama_sekolah'=>'SD Uji','npsn'=>'12345678','nama_pemda'=>'Pemerintah Kabupaten Uji','nama_dinas'=>'Dinas Pendidikan','tempat_cetak'=>'Uji','alamat'=>'Jalan Sekolah','kepala_sekolah'=>'Kepala Uji','bendahara'=>'Bendahara Uji','pemeriksa'=>'Pemeriksa Uji','kode_surat'=>'400.3.5.5','kode_sekolah'=>'SDUJI']);
  $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a5u0AAAAASUVORK5CYII=');Storage::disk('local')->put('v3/logo.png',$png);$s->update(['logo_pemda'=>'v3/logo.png','logo_sekolah'=>'v3/logo.png']);
  $this->writer=User::factory()->create(['school_profile_id'=>$s->id,'role'=>'bendahara']);$this->reviewer=User::factory()->create(['school_profile_id'=>$s->id,'role'=>'kepala_sekolah']);
  $b=Budget::create(['school_profile_id'=>$s->id,'tahun'=>2026,'sumber_dana'=>'BOS','nilai'=>50000000]);
  $this->activity=Activity::create(['school_profile_id'=>$s->id,'budget_id'=>$b->id,'nama'=>'Pengecatan lapangan','created_by'=>$this->writer->id]);$this->actingAs($this->writer);
 }
 private function payload():array{return ['nomor_bukti'=>'B-001','tanggal'=>'2026-01-19','kategori'=>'Barang','vendor_nama'=>'Karya Bersama','vendor_alamat'=>'Balanipa','vendor_penerima'=>'Penerima Toko','items'=>[['nama'=>'Cat','volume'=>'14','satuan'=>'Kelang','harga'=>'190000']],'components'=>[['label'=>'PPN','kategori'=>'pajak','metode'=>'nominal','nominal'=>'263603','perlakuan'=>'termasuk']]];}
 private function purchase():Purchase{return app(V3Workflow::class)->save($this->activity,$this->payload());}
 private function issueAll(Purchase $p):void {
  $wf=app(V3Workflow::class);$wf->issue($p->id,['jenis'=>'nopes','tanggal'=>'2026-01-19']);$wf->issue($p->id,['jenis'=>'bapb','tanggal'=>'2026-01-20','inspection'=>[1=>['baik'=>'14','rusak'=>'0']]]);$wf->issue($p->id,['jenis'=>'kuitansi','tanggal'=>'2026-01-21','tanggal_lunas'=>'2026-01-21']);
 }
 public function test_fixed_connection_does_not_use_old_environment():void{$this->assertSame('silpj_v3',config('database.default'));$this->assertSame(['silpj_v3'],array_keys(config('database.connections')));$this->assertSame(':memory:',config('database.connections.silpj_v3.database'));$this->assertDatabaseHas('v3_identity',['product'=>'silpj-v3']);}
 public function test_manual_included_tax_does_not_increase_total():void{$p=$this->purchase();$this->assertEquals(2660000,$p->bruto);$this->assertEquals(2660000,$p->netto);$this->assertEquals(263603,$p->total_pajak);}
 public function test_percentage_and_fractional_quantities_are_rounded_consistently():void{$c=V3Calculator::calculate([['nama'=>'Kabel','volume'=>'1.125','satuan'=>'m','harga'=>'101']], [['label'=>'A','kategori'=>'pajak','metode'=>'persentase','persentase'=>'10','dasar'=>'1000','perlakuan'=>'tambah'],['label'=>'B','kategori'=>'pajak','metode'=>'nominal','nominal'=>'20','perlakuan'=>'potong']]);$this->assertSame('114',$c['totals']['subtotal']);$this->assertSame('214',$c['totals']['bruto']);$this->assertSame('194',$c['totals']['netto']);}
 public function test_save_multiple_items_and_validation():void{$d=$this->payload();$d['items'][]=['nama'=>'Kuas','volume'=>'2.5','satuan'=>'pak','harga'=>'10000'];$this->post(route('v3.store',$this->activity->id),$d)->assertSessionHasNoErrors()->assertRedirect();$this->assertDatabaseCount('v3_items',2);$this->assertDatabaseHas('v3_purchases',['bruto'=>2685000]);$d['nomor_bukti']='B-002';$d['items'][0]['volume']=-1;$this->post(route('v3.store',$this->activity->id),$d)->assertSessionHasErrors('items.0.volume');}
 public function test_budget_overspending_is_rejected_atomically():void{$d=$this->payload();$d['items'][0]['harga']=10000000;$this->post(route('v3.store',$this->activity->id),$d)->assertSessionHasErrors('items');$this->assertDatabaseCount('v3_purchases',0);$this->assertDatabaseCount('v3_vendors',0);}
 public function test_duplicate_number_is_rejected():void{$this->purchase();$this->post(route('v3.store',$this->activity->id),$this->payload())->assertSessionHasErrors('nomor_bukti');$this->assertDatabaseCount('v3_purchases',1);}
 public function test_document_sequence_and_inspection_validation():void{$p=$this->purchase();$this->post(route('v3.issue',$p->id),['jenis'=>'bapb','tanggal'=>'2026-01-20','inspection'=>[1=>['baik'=>14,'rusak'=>0]]])->assertSessionHasErrors('jenis');app(V3Workflow::class)->issue($p->id,['jenis'=>'nopes','tanggal'=>'2026-01-19']);$this->post(route('v3.issue',$p->id),['jenis'=>'bapb','tanggal'=>'2026-01-20','inspection'=>[1=>['baik'=>13,'rusak'=>0]]])->assertSessionHasErrors('inspection');$this->assertDatabaseCount('v3_documents',1);}
 public function test_snapshot_and_rendering_stay_frozen():void{$p=$this->purchase();$this->issueAll($p);$doc=Document::where('jenis','kuitansi')->first();SchoolProfile::find($this->writer->school_profile_id)->update(['nama_sekolah'=>'Nama Berubah']);$this->get(route('v3.document',$doc->id))->assertOk()->assertSee('SD Uji')->assertSee('Pengecatan lapangan')->assertDontSee('Nama Berubah');$this->get(route('v3.document',['id'=>$doc->id,'pdf'=>1]))->assertOk()->assertHeader('content-type','application/pdf');}
 public function test_review_locks_data_and_requires_separate_reviewer():void{$p=$this->purchase();$this->issueAll($p);$this->post(route('v3.upload',$p->id),['file'=>UploadedFile::fake()->create('bukti.pdf',10,'application/pdf')])->assertSessionHasNoErrors();$this->post(route('v3.transition',$p->id),['action'=>'ajukan'])->assertSessionHasNoErrors();$this->put(route('v3.update',[$this->activity->id,$p->id]),$this->payload()+['reason'=>'Perbaikan data'])->assertSessionHasErrors('status');$this->post(route('v3.transition',$p->id),['action'=>'setujui'])->assertForbidden();$this->actingAs($this->reviewer)->post(route('v3.transition',$p->id),['action'=>'setujui'])->assertSessionHasNoErrors();$this->assertDatabaseHas('v3_purchases',['id'=>$p->id,'status'=>'disetujui']);}
 public function test_revision_supersedes_old_documents():void{$p=$this->purchase();$this->issueAll($p);$d=$this->payload();$d['reason']='Koreksi harga barang';$d['items'][0]['harga']=180000;$this->put(route('v3.update',[$this->activity->id,$p->id]),$d)->assertSessionHasNoErrors();$this->assertDatabaseHas('v3_purchases',['id'=>$p->id,'revision'=>2]);$this->assertEquals(3,Document::where('status','diganti')->count());}
 public function test_cross_school_access_is_denied():void{$p=$this->purchase();$this->issueAll($p);$s=SchoolProfile::create(['nama_sekolah'=>'Sekolah Lain']);$u=User::factory()->create(['school_profile_id'=>$s->id,'role'=>'admin']);$this->actingAs($u)->get(route('v3.purchase',$p->id))->assertNotFound();$this->get(route('v3.document',Document::first()->id))->assertNotFound();$this->put(route('v3.update',[$this->activity->id,$p->id]),$this->payload()+['reason'=>'Percobaan akses'])->assertNotFound();}
 public function test_cancel_nopes_cancels_downstream_documents():void{$p=$this->purchase();$this->issueAll($p);$doc=Document::where('jenis','nopes')->first();$this->post(route('v3.document.cancel',$doc->id),['reason'=>'Pesanan dibatalkan'])->assertSessionHasNoErrors();$this->assertEquals(3,Document::where('status','dibatalkan')->count());}
 public function test_all_main_pages_render():void{$p=$this->purchase();foreach(['dashboard','v3.budgets','v3.settings','v3.reports'] as $route)$this->get(route($route))->assertOk();$this->get(route('v3.create',$this->activity->id))->assertOk();$this->get(route('v3.purchase',$p->id))->assertOk();$this->get(route('v3.edit',[$this->activity->id,$p->id]))->assertOk();}
 public function test_public_registration_is_disabled():void{$this->post('/register',[])->assertNotFound();}

 public function test_approval_queue_is_restricted_to_reviewers():void {
  $this->get(route('v3.approvals'))->assertForbidden();
  $this->actingAs($this->reviewer)->get(route('dashboard'))->assertRedirect(route('v3.approvals'));
  $this->get(route('v3.approvals'))->assertOk()->assertSee('Persetujuan LPJ')->assertSee('role-head');
 }
 public function test_approval_queue_filters_status_year_and_search():void {
  $p=$this->purchase();$p->update(['status'=>'diajukan','submitted_by'=>$this->writer->id]);
  $other=$p->replicate();$other->nomor_bukti='APPROVED-ONLY';$other->status='disetujui';$other->save();
  $this->actingAs($this->reviewer)->get(route('v3.approvals'))->assertOk()->assertSee('B-001')->assertDontSee('APPROVED-ONLY');
  $this->get(route('v3.approvals',['status'=>'disetujui']))->assertOk()->assertSee('APPROVED-ONLY')->assertDontSee('B-001');
  $this->get(route('v3.approvals',['tahun'=>2025]))->assertOk()->assertDontSee('B-001');
  $this->get(route('v3.approvals',['q'=>'Pengecatan']))->assertOk()->assertSee('B-001');
  $this->get(route('v3.approvals',['q'=>'NO-MATCH']))->assertOk()->assertDontSee('B-001');
  $this->get(route('v3.approvals',['status'=>'invalid']))->assertSessionHasErrors('status');
 }
 public function test_approval_queue_does_not_expose_other_schools():void {
  $p=$this->purchase();$p->update(['status'=>'diajukan']);
  $school=SchoolProfile::create(['nama_sekolah'=>'Sekolah Terpisah']);
  $reviewer=User::factory()->create(['school_profile_id'=>$school->id,'role'=>'kepala_sekolah']);
  $this->actingAs($reviewer)->get(route('v3.approvals'))->assertOk()->assertDontSee('B-001')->assertViewHas('counts',fn($counts)=>$counts->isEmpty());
 }
 public function test_reviewer_can_open_transaction_with_review_actions_at_top():void {
  $p=$this->purchase();$p->update(['status'=>'diajukan','submitted_by'=>$this->writer->id]);
  $this->actingAs($this->reviewer)->get(route('v3.purchase',$p->id))->assertOk()
   ->assertSeeInOrder(['Kembali ke daftar persetujuan','Setujui LPJ','Bukti transaksi']);
 }
}
