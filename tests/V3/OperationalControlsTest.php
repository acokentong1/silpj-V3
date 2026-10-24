<?php

namespace Tests\V3;

use App\Exports\V3Export;
use App\Models\SchoolProfile;
use App\Models\User;
use App\Models\V3\Activity;
use App\Models\V3\Budget;
use App\Models\V3\Document;
use App\Models\V3\Purchase;
use App\Services\DocumentAssets;
use App\Services\ReceiptService;
use App\Services\V3Calculator;
use App\Services\V3Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalControlsTest extends TestCase
{
    use RefreshDatabase;

    private User $writer;

    private User $reviewer;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $s = SchoolProfile::create(['nama_sekolah' => 'SD Uji', 'npsn' => '12345678', 'nama_pemda' => 'Pemerintah Kabupaten Uji', 'nama_dinas' => 'Dinas Pendidikan', 'tempat_cetak' => 'Uji', 'alamat' => 'Jalan Sekolah', 'kepala_sekolah' => 'Kepala Uji', 'bendahara' => 'Bendahara Uji', 'pemeriksa' => 'Pemeriksa Uji', 'kode_surat' => '400.3.5.5', 'kode_sekolah' => 'SDUJI']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a5u0AAAAASUVORK5CYII=');
        Storage::disk('local')->put('v3/logo.png', $png);
        $s->update(['logo_pemda' => 'v3/logo.png', 'logo_sekolah' => 'v3/logo.png']);
        $this->writer = User::factory()->create(['school_profile_id' => $s->id, 'role' => 'bendahara']);
        $this->reviewer = User::factory()->create(['school_profile_id' => $s->id, 'role' => 'kepala_sekolah']);
        $b = Budget::create(['school_profile_id' => $s->id, 'tahun' => 2026, 'sumber_dana' => 'BOS', 'nilai' => 50000000]);
        $this->activity = Activity::create(['school_profile_id' => $s->id, 'budget_id' => $b->id, 'nama' => 'Pengecatan lapangan', 'created_by' => $this->writer->id]);
        $this->actingAs($this->writer);
    }

    private function payload(): array
    {
        return ['expected_revision'=>1, 'nomor_bukti' => 'B-001', 'tanggal' => '2026-01-19', 'kategori' => 'Barang', 'vendor_nama' => 'Karya Bersama', 'vendor_alamat' => 'Balanipa', 'vendor_penerima' => 'Penerima Toko', 'items' => [['nama' => 'Cat', 'volume' => '14', 'satuan' => 'Kelang', 'harga' => '190000']], 'components' => [['label' => 'PPN', 'kategori' => 'pajak', 'metode' => 'nominal', 'nominal' => '263603', 'perlakuan' => 'termasuk']]];
    }

    private function purchase(): Purchase
    {
        return app(V3Workflow::class)->save($this->activity, $this->payload());
    }

    private function issueAll(Purchase $p): void
    {
        $wf = app(V3Workflow::class);
        $wf->issue($p->id, ['jenis' => 'nopes', 'tanggal' => '2026-01-19']);
        $wf->issue($p->id, ['jenis' => 'bapb', 'tanggal' => '2026-01-20', 'inspection' => [1 => ['baik' => '14', 'rusak' => '0']]]);
        $wf->issue($p->id, ['jenis' => 'kuitansi', 'tanggal' => '2026-01-21', 'tanggal_lunas' => '2026-01-21']);
    }


    public function test_stale_edit_is_rejected_without_overwriting(): void {
        $p=$this->purchase(); $d=$this->payload()+['reason'=>'Koreksi pertama'];
        $d['items'][0]['harga']='180000';
        $this->put(route('v3.update',[$this->activity->id,$p->id]),$d)->assertSessionHasNoErrors();
        $d['items'][0]['harga']='170000';
        $this->put(route('v3.update',[$this->activity->id,$p->id]),$d)->assertSessionHasErrors('expected_revision');
        $this->assertEquals(2520000,$p->fresh()->bruto);
    }
    public function test_evidence_requires_confirmation_after_revision(): void {
        $p=$this->purchase();
        $this->post(route('v3.upload',$p->id),['file'=>UploadedFile::fake()->create('proof.pdf',1,'application/pdf')])->assertSessionHasNoErrors();
        app(V3Workflow::class)->save($this->activity,$this->payload()+['reason'=>'Koreksi dokumen'],$p->id);
        $p->refresh(); $this->issueAll($p);
        $this->post(route('v3.transition',$p->id),['action'=>'ajukan'])->assertSessionHasErrors('status');
        $id=DB::table('v3_attachments')->value('id');
        $this->post(route('v3.evidence',$id),['action'=>'confirm','reason'=>'Bukti masih sesuai'])->assertSessionHasNoErrors();
        $this->post(route('v3.transition',$p->id),['action'=>'ajukan'])->assertSessionHasNoErrors();
    }
    public function test_excel_amounts_are_numeric_and_untrusted_text_is_not_formula(): void {
        $sheet=new \PhpOffice\PhpSpreadsheet\Spreadsheet(); $e=new V3Export($this->writer->school_profile_id);
        $cell=$sheet->getActiveSheet()->getCell('G2'); $e->bindValue($cell,'12345.00');
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC,$cell->getDataType());
        $text=$sheet->getActiveSheet()->getCell('F2');$e->bindValue($text,'=1+1');
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,$text->getDataType());
    }
    public function test_session_reset_blocks_previous_session_version(): void {
        $p=$this->purchase();$this->writer->forceFill(['session_version'=>2])->save();
        $this->withSession(['account_version'=>1])->get(route('v3.purchase',$p->id))->assertRedirect(route('login'));
    }
    public function test_admin_reset_updates_version_and_remember_token(): void {
        $admin=User::factory()->create(['school_profile_id'=>$this->writer->school_profile_id,'role'=>'admin']);
        $this->actingAs($admin)->patch(route('v3.accounts.update',$this->writer->id),['role'=>'bendahara','is_active'=>1,'password'=>'NewPassword123!','password_confirmation'=>'NewPassword123!'])->assertSessionHasNoErrors();
        $this->assertEquals(2,$this->writer->fresh()->session_version);
        $this->assertNotEmpty($this->writer->fresh()->remember_token);
    }
    public function test_remaining_pages_render_without_removed_navigation(): void {
        $p=$this->purchase();
        foreach(['dashboard','v3.audit','v3.notices','v3.vendors','v3.budgets'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('Kas & periode')->assertDontSee('Ringkasan anggaran');
        }
        $this->get(route('v3.purchase',$p->id))->assertOk()->assertDontSee('belanja-pembayaran')->assertSee('Dokumen LPJ');
        $this->actingAs($this->reviewer)->get(route('dashboard'))->assertRedirect(route('v3.approvals'));
    }
    public function test_removed_cash_period_and_summary_urls_are_unavailable(): void {
        foreach([$this->writer,$this->reviewer] as $user) {
            $this->actingAs($user);
            foreach(['/ringkasan','/operasional'] as $url) $this->get($url)->assertNotFound();
            foreach(['/operasional/kas','/operasional/kas/1/batalkan','/operasional/periode'] as $url) $this->post($url,[])->assertNotFound();
        }
    }
    public function test_historical_cash_and_closed_period_do_not_block_revision_but_approval_still_locks(): void {
        $p=$this->purchase();$this->issueAll($p);
        DB::table('v3_periods')->insert(['school_profile_id'=>$p->school_profile_id,'tahun'=>2026,'bulan'=>1,'is_closed'=>1,'reason'=>'Arsip periode lama','actor_id'=>$this->reviewer->id]);
        DB::table('v3_cash_entries')->insert(['school_profile_id'=>$p->school_profile_id,'purchase_id'=>$p->id,'token'=>(string)Str::uuid(),'tanggal'=>'2026-01-25','direction'=>'out','sumber_dana'=>'BOS','amount'=>1000000,'method'=>'bank','reference'=>'ARSIP','description'=>'Catatan lama','created_by'=>$this->writer->id]);
        $p->update(['status'=>'disetujui']);
        $this->put(route('v3.update',[$this->activity->id,$p->id]),$this->payload()+['reason'=>'Uji transaksi terkunci'])->assertSessionHasErrors('status');
        $this->actingAs($this->reviewer)->post(route('v3.transition',$p->id),['action'=>'buka','reason'=>'Koreksi ulang LPJ'])->assertSessionHasNoErrors();
        $this->actingAs($this->writer)->put(route('v3.update',[$this->activity->id,$p->id]),$this->payload()+['reason'=>'Koreksi data LPJ'])->assertSessionHasNoErrors();
        $this->post(route('v3.budgets.save'),['tahun'=>2026,'sumber_dana'=>'BOS','nilai'=>50000000])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('v3_cash_entries',1);
        $this->assertDatabaseHas('v3_periods',['is_closed'=>1]);
    }
    public function test_receipt_correction_invalidates_dependent_documents(): void {
        $p=$this->purchase();app(V3Workflow::class)->issue($p->id,['jenis'=>'nopes','tanggal'=>'2026-01-19']);
        app(ReceiptService::class)->receive($p->id,['token'=>(string)Str::uuid(),'tanggal'=>'2026-01-20','items'=>[1=>['baik'=>14,'rusak'=>0]]]);
        app(V3Workflow::class)->issue($p->id,['jenis'=>'bapb','tanggal'=>'2026-01-20','inspection'=>[]]);
        $id=DB::table('v3_receipts')->value('id');
        $this->post(route('v3.receipts.cancel',$id),['reason'=>'Koreksi penerimaan'])->assertSessionHasNoErrors();
        $this->assertSame([],ReceiptService::totals($p));
        $this->assertNull(V3Workflow::current($p,'bapb'));
    }
    public function test_final_report_excludes_drafts_and_includes_approved_documents(): void {
        $p=$this->purchase();$this->issueAll($p);
        $this->post(route('v3.upload',$p->id),['file'=>UploadedFile::fake()->create('proof.pdf',1,'application/pdf')]);
        $this->get(route('v3.final',['tahun'=>2026]))->assertSessionHasErrors('laporan');
        $p->update(['status'=>'disetujui','approved_by'=>$this->reviewer->id,'approved_at'=>now()]);
        $response=$this->get(route('v3.final',['tahun'=>2026]));$response->assertOk()->assertHeader('content-type','application/pdf');
    }
    public function test_service_document_uses_work_inspection_and_working_status(): void {
        $d=$this->payload();$d['kategori']='Jasa';$p=app(V3Workflow::class)->save($this->activity,$d);$this->issueAll($p);
        $doc=Document::where('jenis','bapb')->first();
        $this->get(route('v3.document',$doc->id))->assertOk()->assertSee('BERITA ACARA PEMERIKSAAN PEKERJAAN')->assertSee('BELUM DISETUJUI');
    }
    public function test_notice_created_for_reviewer_and_not_visible_to_other_user(): void {
        $p=$this->purchase();$this->issueAll($p);
        $this->post(route('v3.upload',$p->id),['file'=>UploadedFile::fake()->create('proof.pdf',1,'application/pdf')]);
        $this->post(route('v3.transition',$p->id),['action'=>'ajukan'])->assertSessionHasNoErrors();
        $id=DB::table('v3_notices')->value('id');
        $this->post(route('v3.notices.read',$id))->assertNotFound();
        $this->actingAs($this->reviewer)->post(route('v3.notices.read',$id))->assertRedirect(route('v3.purchase',$p->id));
    }
    public function test_activity_archival_preserves_existing_transactions_and_blocks_new(): void {
        $p=$this->purchase();
        $this->patch(route('v3.activity.update',$this->activity->id),['nama'=>$this->activity->nama,'keterangan'=>'','is_archived'=>1,'reason'=>'Kegiatan selesai'])->assertSessionHasNoErrors();
        $d=$this->payload();$d['nomor_bukti']='B-NEW';
        $this->post(route('v3.store',$this->activity->id),$d)->assertStatus(422);
        $this->get(route('v3.purchase',$p->id))->assertOk();
    }
}
