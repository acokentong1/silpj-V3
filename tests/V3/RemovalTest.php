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

class RemovalTest extends TestCase
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


    public function test_delete_draft_preserves_audit_and_hides_from_active_list(): void {
        $p=$this->purchase();$this->issueAll($p);
        $this->delete(route('v3.purchase.remove',$p->id),['reason'=>'Salah input transaksi','expected_revision'=>1])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('v3_purchases',['id'=>$p->id,'status'=>'dibatalkan']);
        $this->assertSame(3,$p->documents()->where('status','dibatalkan')->count());
        $this->assertDatabaseHas('v3_events',['purchase_id'=>$p->id,'action'=>'hapus_dari_daftar']);
        $this->get(route('v3.activity',$this->activity->id))->assertViewHas('purchases',fn($q)=>$q->total()===0);
        $this->get(route('v3.activity',[$this->activity->id,'show_cancelled'=>1]))->assertViewHas('purchases',fn($q)=>$q->total()===1);
    }
    public function test_approved_and_stale_transactions_cannot_be_removed(): void {
        $p=$this->purchase();
        $this->delete(route('v3.purchase.remove',$p->id),['reason'=>'Salah input transaksi','expected_revision'=>2])->assertSessionHasErrors('hapus');
        $p->update(['status'=>'disetujui']);
        $this->delete(route('v3.purchase.remove',$p->id),['reason'=>'Salah input transaksi','expected_revision'=>1])->assertSessionHasErrors('status');
        $this->assertSame('disetujui',$p->fresh()->status);
    }
    public function test_activity_requires_no_active_transactions_and_is_recoverable(): void {
        $p=$this->purchase();
        $this->delete(route('v3.activity.remove',$this->activity->id),['reason'=>'Kegiatan tidak dipakai'])->assertSessionHasErrors('hapus');
        $this->delete(route('v3.purchase.remove',$p->id),['reason'=>'Salah input transaksi','expected_revision'=>1])->assertSessionHasNoErrors();
        $this->delete(route('v3.activity.remove',$this->activity->id),['reason'=>'Kegiatan tidak dipakai'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('v3_activities',['id'=>$this->activity->id,'is_archived'=>1]);
        $this->get(route('dashboard',['tahun'=>2026]))->assertViewHas('activities',fn($a)=>$a->total()===0);
        $this->get(route('dashboard',['tahun'=>2026,'show_archived'=>1]))->assertViewHas('activities',fn($a)=>$a->total()===1);
        $this->patch(route('v3.activity.update',$this->activity->id),['nama'=>$this->activity->nama,'is_archived'=>0,'reason'=>'Aktifkan kembali kegiatan'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('v3_activities',['id'=>$this->activity->id,'is_archived'=>0]);
    }
    public function test_delete_checks_role_school_and_reason(): void {
        $p=$this->purchase();
        $this->delete(route('v3.purchase.remove',$p->id),['expected_revision'=>1])->assertSessionHasErrors('reason');
        $this->actingAs($this->reviewer)->delete(route('v3.purchase.remove',$p->id),['expected_revision'=>1,'reason'=>'Tidak diizinkan'])->assertForbidden();
        $school=SchoolProfile::create(['nama_sekolah'=>'Sekolah lain']);
        $other=User::factory()->create(['school_profile_id'=>$school->id,'role'=>'bendahara']);
        $this->actingAs($other)->delete(route('v3.purchase.remove',$p->id),['expected_revision'=>1,'reason'=>'Tidak diizinkan'])->assertNotFound();
        $this->delete(route('v3.activity.remove',$this->activity->id),['reason'=>'Tidak diizinkan'])->assertNotFound();
        $this->assertSame('draf',$p->fresh()->status);
    }
}
