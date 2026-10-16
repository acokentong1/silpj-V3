<?php
namespace Tests\V3;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Storage};
use App\Models\{SchoolProfile,User};
use App\Models\V3\{Activity,Budget,Document};
use App\Services\V3Workflow;

class MultiSchoolTest extends TestCase {
 use RefreshDatabase;
 private User $a;private User $b;private Activity $activityA;private Activity $activityB;
 protected function setUp():void {
  parent::setUp();Storage::fake('local');
  [$this->a,$this->activityA]=$this->fixture('A','10000001');
  [$this->b,$this->activityB]=$this->fixture('B','10000002');
  $this->actingAs($this->a);
 }
 private function fixture(string $suffix,string $npsn):array {
  $s=SchoolProfile::create(['nama_sekolah'=>'Sekolah '.$suffix,'npsn'=>$npsn,'is_active'=>true]);
  $u=User::factory()->create(['school_profile_id'=>$s->id,'role'=>'admin']);
  $b=Budget::create(['school_profile_id'=>$s->id,'tahun'=>2026,'sumber_dana'=>'BOS','nilai'=>'50000000']);
  $a=Activity::create(['school_profile_id'=>$s->id,'budget_id'=>$b->id,'nama'=>'Kegiatan rahasia '.$suffix,'created_by'=>$u->id]);
  return [$u,$a];
 }
 private function payload():array{return ['nomor_bukti'=>'B-001','tanggal'=>'2026-01-19','kategori'=>'Barang','vendor_nama'=>'Toko','vendor_penerima'=>'Penerima','items'=>[['nama'=>'Buku','volume'=>'2','satuan'=>'buah','harga'=>'10000']]];}
 private function schoolPayload():array{return ['nama_sekolah'=>'Sekolah C','npsn'=>'10000003','alamat'=>'Jalan C','name'=>'Admin C','email'=>'admin-c@example.test','password'=>'Password-test-123','password_confirmation'=>'Password-test-123'];}
 public function test_school_admin_cannot_manage_schools_or_download_system_backup():void {
  $this->get(route('system.schools'))->assertForbidden();
  $this->post(route('system.schools.store'),$this->schoolPayload())->assertForbidden();
  $this->patch(route('system.schools.update',$this->b->school_profile_id),['is_active'=>0,'reason'=>'Nonaktifkan'])->assertForbidden();
  $this->post(route('v3.backup'),['password'=>'password'])->assertForbidden();
 }
 public function test_system_admin_can_create_school_but_does_not_gain_cross_school_access():void {
  $this->a->forceFill(['is_system_admin'=>true])->save();
  $this->get(route('system.schools'))->assertOk();
  $this->post(route('system.schools.store'),$this->schoolPayload())->assertSessionHasNoErrors()->assertRedirect(route('system.schools'));
  $s=SchoolProfile::where('npsn','10000003')->firstOrFail();
  $this->assertDatabaseHas('users',['email'=>'admin-c@example.test','school_profile_id'=>$s->id,'role'=>'admin','is_system_admin'=>false]);
  $this->assertDatabaseHas('system_events',['school_profile_id'=>$s->id,'action'=>'buat_sekolah']);
  $this->get(route('v3.activity',$this->activityB->id))->assertNotFound();
 }
 public function test_duplicate_npsn_rejected_without_partial_school_or_user():void {
  $this->a->forceFill(['is_system_admin'=>true])->save();$d=$this->schoolPayload();$d['npsn']='10000002';
  $this->post(route('system.schools.store'),$d)->assertSessionHasErrors('npsn');
  $this->assertDatabaseCount('school_profiles',2);$this->assertDatabaseCount('users',2);
 }
 public function test_foreign_records_and_mutations_are_denied():void {
  $this->actingAs($this->b);$p=app(V3Workflow::class)->save($this->activityB,$this->payload());
  $d=Document::create(['school_profile_id'=>$this->b->school_profile_id,'purchase_id'=>$p->id,'jenis'=>'nopes','nomor'=>'NOPES-001','tanggal'=>'2026-01-19','versi'=>1,'revision'=>1,'snapshot'=>[],'created_by'=>$this->b->id]);
  $f=DB::table('v3_attachments')->insertGetId(['purchase_id'=>$p->id,'name'=>'bukti.pdf','path'=>'v3/private-b.pdf','mime'=>'application/pdf','sha256'=>str_repeat('0',64)]);
  $this->actingAs($this->a);
  foreach([route('v3.activity',$this->activityB->id),route('v3.activity.documents',$this->activityB->id),route('v3.purchase',$p->id),route('v3.document',$d->id),route('v3.attachment',$f),route('v3.budgets.edit',$this->activityB->budget_id)] as $url)$this->get($url)->assertNotFound();
  $this->post(route('v3.activity.store'),['nama'=>'Serangan','budget_id'=>$this->activityB->budget_id])->assertSessionHasErrors('budget_id');
  $this->put(route('v3.update',[$this->activityB->id,$p->id]),$this->payload()+['reason'=>'Ubah harga'])->assertNotFound();
  $this->post(route('v3.transition',$p->id),['action'=>'batalkan','reason'=>'Coba akses'])->assertNotFound();
  $this->post(route('v3.document.cancel',$d->id),['reason'=>'Coba akses'])->assertNotFound();
  $this->patch(route('v3.accounts.update',$this->b->id),['role'=>'admin','is_active'=>false])->assertNotFound();
  $this->delete(route('v3.budgets.destroy',$this->activityB->budget_id),['reason'=>'Coba akses'])->assertNotFound();
 }
 public function test_same_receipt_number_is_allowed_in_different_schools():void {
  $first=app(V3Workflow::class)->save($this->activityA,$this->payload());
  $this->actingAs($this->b);$second=app(V3Workflow::class)->save($this->activityB,$this->payload());
  $this->assertSame($first->nomor_bukti,$second->nomor_bukti);$this->assertNotEquals($first->school_profile_id,$second->school_profile_id);
 }
 public function test_listing_and_csv_only_contain_current_school():void {
  app(V3Workflow::class)->save($this->activityA,$this->payload());
  $this->actingAs($this->b);app(V3Workflow::class)->save($this->activityB,$this->payload());
  $this->actingAs($this->a);
  $this->get(route('dashboard'))->assertOk()->assertSee('Kegiatan rahasia A')->assertDontSee('Kegiatan rahasia B');
  $this->get(route('v3.reports'))->assertOk()->assertSee('Kegiatan rahasia A')->assertDontSee('Kegiatan rahasia B');
  $csv=$this->get(route('v3.csv'))->assertOk()->streamedContent();
  $this->assertStringContainsString('Kegiatan rahasia A',$csv);$this->assertStringNotContainsString('Kegiatan rahasia B',$csv);
 }
 public function test_school_and_system_privileges_cannot_be_injected_when_creating_user():void {
  $d=['name'=>'New','email'=>'new@example.test','password'=>'password123','password_confirmation'=>'password123','role'=>'admin','school_profile_id'=>$this->b->school_profile_id,'is_system_admin'=>true];
  $this->post(route('v3.accounts.save'),$d)->assertSessionHasNoErrors();
  $this->assertDatabaseHas('users',['email'=>'new@example.test','school_profile_id'=>$this->a->school_profile_id,'is_system_admin'=>false]);
 }
 public function test_school_admin_cannot_reset_system_admin_password():void {
  $system=User::factory()->create(['school_profile_id'=>$this->a->school_profile_id,'role'=>'admin','is_system_admin'=>true]);
  $this->patch(route('v3.accounts.update',$system->id),['role'=>'user','is_active'=>false,'password'=>'new-password','password_confirmation'=>'new-password'])->assertNotFound();
  $this->get(route('admin.users.create'))->assertOk()->assertDontSee($system->email);
 }
 public function test_disabled_school_is_blocked_and_can_be_reactivated():void {
  $this->a->forceFill(['is_system_admin'=>true])->save();
  $this->patch(route('system.schools.update',$this->b->school_profile_id),['is_active'=>0,'reason'=>'Ditutup sementara'])->assertSessionHasNoErrors();
  $this->actingAs($this->b);$this->get(route('dashboard'))->assertForbidden();$this->get(route('v3.csv'))->assertForbidden();
  $this->actingAs($this->a);$this->patch(route('system.schools.update',$this->b->school_profile_id),['is_active'=>1,'reason'=>'Aktifkan kembali'])->assertSessionHasNoErrors();
  $this->actingAs($this->b->fresh());$this->get(route('dashboard'))->assertOk();
 }
 public function test_database_rejects_cross_school_relationship():void {
  $this->expectException(\Illuminate\Database\QueryException::class);
  DB::table('v3_activities')->where('id',$this->activityA->id)->update(['budget_id'=>$this->activityB->budget_id]);
 }
 public function test_database_rejects_moving_user_to_another_school():void {
  $this->expectException(\Illuminate\Database\QueryException::class);
  DB::table('users')->where('id',$this->a->id)->update(['school_profile_id'=>$this->b->school_profile_id]);
 }
 public function test_same_document_number_is_allowed_for_different_schools():void {
  $pa=app(V3Workflow::class)->save($this->activityA,$this->payload());
  $this->actingAs($this->b);$pb=app(V3Workflow::class)->save($this->activityB,$this->payload());
  foreach([[$pa,$this->a],[$pb,$this->b]] as [$purchase,$user]) {
   Document::create(['school_profile_id'=>$user->school_profile_id,'purchase_id'=>$purchase->id,'jenis'=>'nopes','nomor'=>'NOPES-001','tanggal'=>'2026-01-19','versi'=>1,'revision'=>1,'snapshot'=>[],'created_by'=>$user->id]);
  }
  $this->assertDatabaseCount('v3_documents',2);
 }
 public function test_database_rejects_document_for_another_school_purchase():void {
  $pa=app(V3Workflow::class)->save($this->activityA,$this->payload());
  $this->expectException(\Illuminate\Database\QueryException::class);
  Document::create(['school_profile_id'=>$this->b->school_profile_id,'purchase_id'=>$pa->id,'jenis'=>'nopes','nomor'=>'INVALID','tanggal'=>'2026-01-19','versi'=>1,'revision'=>1,'snapshot'=>[],'created_by'=>$this->b->id]);
 }
 public function test_system_admin_without_school_can_open_system_page_from_dashboard():void {
  $system=User::factory()->create(['school_profile_id'=>null,'is_system_admin'=>true,'role'=>'user']);
  $this->actingAs($system);
  $this->get('/')->assertRedirect(route('system.schools'));
  $this->get(route('dashboard'))->assertRedirect(route('system.schools'));
  $this->get(route('system.schools'))->assertOk();
  $this->get(route('v3.reports'))->assertForbidden();
  $this->post(route('v3.activity.store'),['nama'=>'Forbidden','budget_id'=>$this->activityA->budget_id])->assertForbidden();
 }
 public function test_system_admin_login_discards_stale_intended_school_url():void {
  $system=User::factory()->create(['school_profile_id'=>null,'is_system_admin'=>true,'role'=>'user']);
  \Illuminate\Support\Facades\Auth::logout();
  $this->withSession(['url.intended'=>route('dashboard')])->post('/login',[
   'email'=>$system->email,'password'=>'password',
  ])->assertRedirect(route('system.schools'))->assertSessionMissing('url.intended');
  $this->assertAuthenticatedAs($system);
 }
 public function test_system_admin_with_disabled_school_keeps_system_access_only():void {
  $this->a->forceFill(['is_system_admin'=>true])->save();
  SchoolProfile::whereKey($this->a->school_profile_id)->update(['is_active'=>false]);
  $this->actingAs($this->a->fresh());
  $this->get(route('dashboard'))->assertRedirect(route('system.schools'));
  $this->get(route('system.schools'))->assertOk();
  $this->get(route('v3.reports'))->assertForbidden();
 }
 public function test_regular_account_without_school_is_not_redirected_to_system():void {
  $user=User::factory()->create(['school_profile_id'=>null,'is_system_admin'=>false]);
  $this->actingAs($user);
  $this->get(route('dashboard'))->assertForbidden();
  $this->get(route('system.schools'))->assertForbidden();
 }
 public function test_system_school_search_and_status_filter():void {
  $this->a->forceFill(['is_system_admin'=>true])->save();
  SchoolProfile::whereKey($this->b->school_profile_id)->update(['is_active'=>false]);
  $this->get(route('system.schools',['q'=>'10000002','status'=>'inactive']))->assertOk()
   ->assertViewHas('schools',fn($schools)=>$schools->total()===1 && $schools->first()->id===$this->b->school_profile_id)
   ->assertViewHas('stats',fn($stats)=>$stats['total']===2 && $stats['active']===1 && $stats['inactive']===1);
  $this->get(route('system.schools',['q'=>'10000002','status'=>'active']))->assertOk()
   ->assertViewHas('schools',fn($schools)=>$schools->total()===0);
  $this->get(route('system.schools',['status'=>'invalid']))->assertSessionHasErrors('status');
 }
}
