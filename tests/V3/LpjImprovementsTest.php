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

class LpjImprovementsTest extends TestCase
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
        $this->post(route('v3.upload',$p->id),['category'=>'pembayaran','file'=>UploadedFile::fake()->create('transfer.pdf',1,'application/pdf')])->assertSessionHasNoErrors();
        $wf->issue($p->id, ['payment_confirmed'=>true, 'jenis' => 'kuitansi', 'tanggal' => '2026-01-21', 'tanggal_lunas' => '2026-01-21']);
    }


    private function complete(): Purchase {
        $p=$this->purchase();
        $this->issueAll($p);
        $this->post(route('v3.upload',$p->id),['category'=>'nota','file'=>UploadedFile::fake()->create('invoice.pdf',1,'application/pdf')])->assertSessionHasNoErrors();
        $this->post(route('v3.transition',$p->id),['action'=>'ajukan'])->assertSessionHasNoErrors();
        $this->actingAs($this->reviewer)->post(route('v3.transition',$p->id),['action'=>'setujui','confirmation'=>1])->assertSessionHasNoErrors();
        $this->actingAs($this->writer);
        return $p->fresh();
    }
    public function test_nopes_cannot_predate_transaction(): void {
        $p=$this->purchase();
        $this->post(route('v3.issue',$p->id),['jenis'=>'nopes','tanggal'=>'2026-01-18'])->assertSessionHasErrors('tanggal');
        $this->assertDatabaseCount('v3_documents',0);
    }
    public function test_kuitansi_can_be_issued_without_uploading_payment_evidence(): void {
        $p=$this->purchase();$wf=app(V3Workflow::class);
        $wf->issue($p->id,['jenis'=>'nopes','tanggal'=>'2026-01-19']);
        $wf->issue($p->id,['jenis'=>'bapb','tanggal'=>'2026-01-20','inspection'=>[1=>['baik'=>14,'rusak'=>0]]]);
        $d=['jenis'=>'kuitansi','tanggal'=>'2026-01-21','tanggal_lunas'=>'2026-01-21'];
        $this->post(route('v3.issue',$p->id),$d)->assertSessionHasErrors('payment_confirmed');
        $this->assertDatabaseCount('v3_attachments',0);
        $this->post(route('v3.issue',$p->id),$d+['payment_confirmed'=>1])->assertSessionHasNoErrors();
        $this->assertTrue(V3Workflow::current($p,'kuitansi')->snapshot['payment_confirmed']);
        $this->assertDatabaseCount('v3_attachments',0);
        $this->post(route('v3.transition',$p->id),['action'=>'ajukan'])->assertSessionHasErrors('status');
    }
    public function test_damaged_goods_block_kuitansi_even_with_payment_evidence(): void {
        $p=$this->purchase();$wf=app(V3Workflow::class);
        $wf->issue($p->id,['jenis'=>'nopes','tanggal'=>'2026-01-19']);
        $wf->issue($p->id,['jenis'=>'bapb','tanggal'=>'2026-01-20','inspection'=>[1=>['baik'=>13,'rusak'=>1]]]);
        $this->post(route('v3.upload',$p->id),['category'=>'pembayaran','file'=>UploadedFile::fake()->create('transfer.pdf',1,'application/pdf')]);
        $this->post(route('v3.issue',$p->id),['jenis'=>'kuitansi','tanggal'=>'2026-01-21','tanggal_lunas'=>'2026-01-21','payment_confirmed'=>1])->assertSessionHasErrors('inspection');
    }
    public function test_payment_alone_does_not_replace_invoice(): void {
        $p=$this->purchase();$this->issueAll($p);
        $this->post(route('v3.transition',$p->id),['action'=>'ajukan'])->assertSessionHasErrors('status');
        $this->assertSame('draf',$p->fresh()->status);
    }
    public function test_archived_pdf_and_zip_stay_identical_after_transaction_revision(): void {
        $p=$this->complete();
        $this->post(route('v3.final.archive'),['tahun'=>2026])->assertSessionHasNoErrors()->assertRedirect();
        $a=DB::table('v3_report_archives')->first();$this->assertNotNull($a);
        $disk=Storage::disk('local');$original=$disk->get($a->pdf_path);$originalZip=$disk->get($a->zip_path);
        $zip=new \ZipArchive;$this->assertTrue($zip->open($disk->path($a->zip_path)));
        $manifest=json_decode($zip->getFromName('manifest.json'),true);
        $this->assertSame(1,$manifest['transactions'][0]['revision']);
        $this->assertCount(2,$manifest['files']);
        $this->assertSame($original,$zip->getFromName('LPJ.pdf'));$zip->close();
        $this->actingAs($this->reviewer)->post(route('v3.transition',$p->id),['action'=>'buka','reason'=>'Koreksi sesudah arsip'])->assertSessionHasNoErrors();
        $this->actingAs($this->writer)->put(route('v3.update',[$this->activity->id,$p->id]),$this->payload()+['reason'=>'Koreksi harga setelah arsip'])->assertSessionHasNoErrors();
        $this->assertSame($original,$disk->get($a->pdf_path));$this->assertSame($originalZip,$disk->get($a->zip_path));
        $this->get(route('v3.final.archived',[$a->id,'pdf']))->assertOk();
        $this->get(route('v3.final.archived',[$a->id,'zip']))->assertOk();
        $this->get(route('v3.reports'))->assertOk()->assertSee($a->id);
        $school=SchoolProfile::create(['nama_sekolah'=>'Lain']);
        $other=User::factory()->create(['school_profile_id'=>$school->id,'role'=>'bendahara']);
        $this->actingAs($other)->get(route('v3.final.archived',[$a->id,'pdf']))->assertNotFound();
    }
    public function test_archive_rejects_modified_evidence_and_leaves_no_partial_archive(): void {
        $p=$this->complete();$file=DB::table('v3_attachments')->where('purchase_id',$p->id)->first();
        Storage::disk('local')->put($file->path,'tampered');
        $this->post(route('v3.final.archive'),['tahun'=>2026])->assertSessionHasErrors('laporan');
        $this->assertDatabaseCount('v3_report_archives',0);
        $this->assertSame([],Storage::disk('local')->allFiles('v3/schools/'.$p->school_profile_id.'/lpj-archives'));
    }
    public function test_archive_download_rejects_corrupted_pdf(): void {
        $this->complete();$this->post(route('v3.final.archive'),['tahun'=>2026])->assertSessionHasNoErrors();
        $a=DB::table('v3_report_archives')->first();Storage::disk('local')->put($a->pdf_path,'corrupt');
        $this->get(route('v3.final.archived',[$a->id,'pdf']))->assertStatus(409);
    }
    public function test_activity_search_is_paginated_and_scoped(): void {
        $p=$this->purchase();
        for($i=2;$i<=23;$i++) { $copy=$p->replicate();$copy->nomor_bukti='PAGE-'.$i;$copy->save(); }
        $this->get(route('v3.activity',$this->activity->id))->assertOk()->assertViewHas('purchases',fn($q)=>$q->total()===23 && $q->count()===20);
        $this->get(route('v3.activity',[$this->activity->id,'q'=>'PAGE-23']))->assertOk()->assertViewHas('purchases',fn($q)=>$q->total()===1);
    }
}
