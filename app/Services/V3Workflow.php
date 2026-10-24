<?php

namespace App\Services;

use App\Models\SchoolProfile;
use App\Models\V3\Activity;
use App\Models\V3\Budget;
use App\Models\V3\Document;
use App\Models\V3\Purchase;
use App\Models\V3\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class V3Workflow
{
    public static function event(string $action, ?Purchase $p = null, ?string $reason = null, array $details = []): void
    {
        DB::table('v3_events')->insert(['school_profile_id' => auth()->user()->school_profile_id, 'purchase_id' => $p?->id, 'actor_id' => auth()->id(), 'action' => $action, 'reason' => $reason, 'details' => json_encode($details), 'created_at' => now(), 'updated_at' => now()]);
    }

    public static function purchase(int $id, bool $lock = false): Purchase
    {
        $q = Purchase::where('school_profile_id', auth()->user()->school_profile_id);
        if ($lock) {
            $q->lockForUpdate();
        }

return $q->findOrFail($id);
    }

    public static function assertEditable(Purchase $p): void
    {
        abort_unless(auth()->user()->isBendahara(), 403);
        if (! $p->editable()) {
            throw ValidationException::withMessages(['status' => 'Transaksi terkunci. Kembalikan untuk revisi terlebih dahulu.']);
        }
    }

    public function save(Activity $activity, array $data, ?int $id = null): Purchase
    {
        abort_unless(auth()->user()->isBendahara(), 403);

        return DB::transaction(function () use ($activity, $data, $id) {
            abort_unless($activity->school_profile_id === auth()->user()->school_profile_id, 404);
            $budget = Budget::whereKey($activity->budget_id)->where('school_profile_id', $activity->school_profile_id)->lockForUpdate()->firstOrFail();
            $activity = Activity::where('school_profile_id',auth()->user()->school_profile_id)->lockForUpdate()->findOrFail($activity->id);
            $p = $id ? self::purchase($id, true) : new Purchase;
            if ($id) {
                self::assertEditable($p);
                if ((int)($data['expected_revision'] ?? 0) !== (int)$p->revision) {
                    throw ValidationException::withMessages(['expected_revision'=>'Transaksi telah berubah. Muat ulang halaman sebelum mengedit; perubahan Anda belum disimpan.']);
                }
                abort_unless($p->activity_id === $activity->id, 404);
            }
            abort_if($activity->is_archived,422,'Kegiatan sudah diarsipkan.');
            $calc = V3Calculator::calculate($data['items'], $data['components'] ?? []);
            $used = Purchase::whereHas('activity', fn ($q) => $q->where('budget_id', $budget->id))->where('status', '!=', 'dibatalkan')->when($id, fn ($q) => $q->where('id', '!=', $id))->select('id', 'bruto')->lazyById(500)->pluck('bruto')->reduce(fn ($carry, $v) => bcadd($carry, (string) $v, 2), '0');
            if (bccomp(bcadd($used, $calc['totals']['bruto'], 2), (string) $budget->nilai, 2) > 0) {
                throw ValidationException::withMessages(['items' => 'Nilai bruto melebihi sisa anggaran. Draf juga mencadangkan anggaran.']);
            }
            if (Purchase::where('school_profile_id', $activity->school_profile_id)->where('tahun', $budget->tahun)->where('nomor_bukti', $data['nomor_bukti'])->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                throw ValidationException::withMessages(['nomor_bukti' => 'Nomor bukti sudah digunakan pada tahun ini.']);
            }
            $before = $id ? $p->load('items', 'components', 'vendor')->toArray() : null;
            // Vendor records are immutable per saved transaction revision.
            $v = Vendor::create(['school_profile_id' => $activity->school_profile_id, 'nama' => $data['vendor_nama'], 'alamat' => $data['vendor_alamat'] ?? null, 'penerima' => $data['vendor_penerima'] ?? null, 'npwp' => $data['vendor_npwp'] ?? null]);
            $p->fill(['school_profile_id' => $activity->school_profile_id, 'activity_id' => $activity->id, 'vendor_id' => $v->id, 'tahun' => $budget->tahun, 'nomor_bukti' => $data['nomor_bukti'], 'tanggal' => $data['tanggal'], 'kategori' => $data['kategori']] + $calc['totals']);
            if (! $id) {
                $p->created_by = auth()->id();
                $p->status = 'draf';
                $p->revision = 1;
            } else {
                $p->revision++;
                $p->documents()->where('status', 'terbit')->update(['status' => 'diganti']);
            }
            $p->save();
            $p->items()->delete();
            $p->components()->delete();
            $p->items()->createMany($calc['items']);
            $p->components()->createMany($calc['components']);
            self::event($id ? 'ubah_transaksi' : 'buat_transaksi', $p, $data['reason'] ?? null, ['before' => $before, 'after' => $p->fresh()->load('items', 'components', 'vendor')->toArray()]);

            return $p;
        }, 3);
    }

    public static function current(Purchase $p, string $kind): ?Document
    {
        return $p->documents()->where('jenis', $kind)->where('revision', $p->revision)->where('status', 'terbit')->first();
    }

    public function issue(int $id, array $data): Document
    {
        return DB::transaction(function () use ($id, $data) {
            $p = self::purchase($id, true);
            self::assertEditable($p);
            $p->load('activity.budget', 'vendor', 'items', 'components');
            $s = SchoolProfile::findOrFail($p->school_profile_id);
            $kind = $data['jenis'];
            if (self::current($p, $kind)) {
                throw ValidationException::withMessages(['jenis' => 'Dokumen aktif sudah ada. Batalkan dengan alasan sebelum membuat pengganti.']);
            }
            $school = $s->toArray();
            $school['logo_pemda_asset'] = DocumentAssets::freeze($s->logo_pemda, $s->id);
            $school['logo_sekolah_asset'] = DocumentAssets::freeze($s->logo_sekolah, $s->id);
            foreach (['nama_pemda', 'nama_dinas', 'nama_sekolah', 'alamat', 'tempat_cetak', 'kepala_sekolah', 'bendahara', 'pemeriksa', 'kode_surat', 'kode_sekolah', 'logo_pemda_asset', 'logo_sekolah_asset'] as $field) {
                if (empty($school[$field])) {
                    throw ValidationException::withMessages(['pengaturan' => 'Lengkapi pengaturan sekolah, pejabat, kode surat, dan kedua logo sebelum menerbitkan dokumen.']);
                }
            }
            $n = self::current($p, 'nopes');
            $b = self::current($p, 'bapb');
            if ($kind !== 'nopes' && ! $n) {
                throw ValidationException::withMessages(['jenis' => 'Terbitkan Nopes terlebih dahulu.']);
            }
            if ($kind === 'kuitansi' && ! $b) {
                throw ValidationException::withMessages(['jenis' => 'Terbitkan BAPB terlebih dahulu.']);
            }
            $min = $kind === 'bapb' ? $n?->tanggal : ($kind === 'kuitansi' ? $b?->tanggal : $p->tanggal);
            if ($min && $data['tanggal'] < $min) {
                throw ValidationException::withMessages(['tanggal' => 'Tanggal dokumen tidak boleh mendahului dokumen sebelumnya.']);
            }
            if ((int) substr($data['tanggal'], 0, 4) !== $p->tahun) {
                throw ValidationException::withMessages(['tanggal' => 'Tanggal dokumen harus sesuai tahun anggaran transaksi.']);
            }
            if ($kind === 'kuitansi') {
                if (LpjReadiness::damaged($b)) {
                    throw ValidationException::withMessages(['inspection'=>'Selesaikan barang rusak / pekerjaan belum sesuai, koreksi penerimaan, lalu terbitkan ulang berita acara sebelum kuitansi.']);
                }
                if (!in_array($data['payment_confirmed'] ?? null, [true, 1, '1', 'yes', 'on'], true)) {
                    throw ValidationException::withMessages(['payment_confirmed'=>'Konfirmasikan bahwa pembayaran kepada rekanan sudah lunas sebelum menerbitkan kuitansi.']);
                }
            }
            $inspection = [];
            if ($kind === 'bapb' && ReceiptService::batches($p)->isNotEmpty()) {
                if ($data['tanggal'] < ReceiptService::batches($p)->max('tanggal')) {
                    throw ValidationException::withMessages(['tanggal' => 'Tanggal BAPB tidak boleh sebelum penerimaan terakhir.']);
                }
                $data['inspection'] = ReceiptService::totals($p);
            }

            if ($kind === 'bapb') {
                foreach ($p->items as $item) {
                    $row = $data['inspection'][$item->urutan] ?? null;
                    if (! $row || bccomp(bcadd((string) $row['baik'], (string) $row['rusak'], 3), (string) $item->volume, 3) !== 0) {
                        throw ValidationException::withMessages(['inspection' => 'Jumlah baik dan tidak baik setiap barang harus sama dengan volume pesanan. Lengkapi seluruh tahap penerimaan sebelum menerbitkan BAPB.']);
                    }
                    $inspection[] = ['nama' => $item->nama, 'satuan' => $item->satuan, 'baik' => $row['baik'], 'rusak' => $row['rusak'], 'catatan' => $row['catatan'] ?? ''];
                }
            }
            if ($kind === 'kuitansi' && ($data['tanggal_lunas'] < $data['tanggal'] || (int) substr($data['tanggal_lunas'], 0, 4) !== $p->tahun)) {
                throw ValidationException::withMessages(['tanggal_lunas' => 'Tanggal lunas harus pada/setelah tanggal kuitansi dan dalam tahun anggaran.']);
            }
            $year = (int) substr($data['tanggal'], 0, 4);
            // All three document types share a B sequence to preserve school/number uniqueness.
            $key = ['school_profile_id' => $s->id, 'jenis' => 'B', 'tahun' => $year];
            $last = (int) DB::table('v3_sequences')->where('school_profile_id', $s->id)->where('tahun', $year)->max('last');
            DB::table('v3_sequences')->insertOrIgnore($key + ['last' => $last]);
            $seq = DB::table('v3_sequences')->where($key)->lockForUpdate()->first();
            $number = $seq->last + 1;
            $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][(int) substr($data['tanggal'], 5, 2) - 1];
            do {
                $nomor = 'B-'.$number.'/'.$s->kode_surat.'/'.$s->kode_sekolah.'/'.$roman.'/'.$year;
                $exists = Document::where('school_profile_id', $s->id)->where('nomor', $nomor)->exists();
                if ($exists) { $number++; }
            } while ($exists);
            DB::table('v3_sequences')->where('id', $seq->id)->update(['last' => $number]);
            $snapshot = ['template_version' => 2, 'payment_confirmed' => $kind === 'kuitansi', 'school' => $school, 'purchase' => $p->toArray(), 'inspection' => $inspection, 'tanggal_lunas' => $data['tanggal_lunas'] ?? null, 'catatan' => $data['catatan'] ?? '', 'nopes_nomor' => $n?->nomor, 'bapb_nomor' => $b?->nomor];
            $doc = Document::create(['purchase_id' => $p->id, 'school_profile_id' => $s->id, 'jenis' => $kind, 'nomor' => $nomor, 'tanggal' => $data['tanggal'], 'versi' => ($p->documents()->where('jenis', $kind)->max('versi') ?? 0) + 1, 'revision' => $p->revision, 'snapshot' => $snapshot, 'created_by' => auth()->id()]);
            self::event('terbit_'.$kind, $p, null, ['document_id' => $doc->id, 'nomor' => $nomor]);

            return $doc;
        }, 3);
    }

    public function transition(int $id, string $action, ?string $reason): void
    {
        DB::transaction(function () use ($id, $action, $reason) {
            $p = self::purchase($id, true);
            $before = $p->status;
            $u = auth()->user();
            if ($action === 'ajukan') {
                self::assertEditable($p);
                foreach (['nopes', 'bapb', 'kuitansi'] as $kind) {
                    if (! self::current($p, $kind)) {
                        throw ValidationException::withMessages(['status' => 'Lengkapi ketiga dokumen versi terbaru terlebih dahulu.']);
                    }
                }
                if (! DB::table('v3_attachments')->where('purchase_id', $id)->where('revision',$p->revision)->where('is_active',true)->exists()) {
                    throw ValidationException::withMessages(['status' => 'Unggah minimal satu bukti transaksi.']);
                }
                if ($missing = LpjReadiness::missing($p)) {
                    throw ValidationException::withMessages(['status'=>implode(' ', $missing)]);
                }
                $p->status = 'diajukan';
                $p->submitted_by = $u->id;
                $p->submitted_at = now();
            } elseif (in_array($action, ['setujui', 'revisi', 'buka'])) {
                abort_unless($u->isKepalaSekolah(), 403);
                abort_unless($p->status === ($action === 'buka' ? 'disetujui' : 'diajukan'), 409);
                if ($action === 'setujui') {
                    abort_if($p->created_by === $u->id || $p->submitted_by === $u->id, 403, 'Pemeriksa harus berbeda dari pembuat/pengaju.');
                    if ($missing = LpjReadiness::missing($p)) {
                        throw ValidationException::withMessages(['status'=>implode(' ', $missing)]);
                    }
                    $p->status = 'disetujui';
                    $p->approved_by = $u->id;
                    $p->approved_at = now();
                } else {
                    $this->reason($reason);
                    $p->status = 'revisi';
                    $p->approved_by = null;
                    $p->approved_at = null;
                }
            } elseif ($action === 'batalkan') {
                self::assertEditable($p);
                $this->reason($reason);
                $p->status = 'dibatalkan';
                $p->documents()->where('status', 'terbit')->update(['status' => 'dibatalkan']);
            } else {
                abort(422);
            }
            $p->save();
            self::event($action, $p, $reason, ['from' => $before, 'to' => $p->status]);
            $recipients = \App\Models\User::where('school_profile_id',$p->school_profile_id)->where('is_active',true)->where('id','!=',$u->id);
            if ($action==='ajukan') $recipients->where('role','kepala_sekolah'); else $recipients->whereIn('role',['admin','bendahara']);
            foreach($recipients->get() as $recipient) DB::table('v3_notices')->insert(['school_profile_id'=>$p->school_profile_id,'purchase_id'=>$p->id,'user_id'=>$recipient->id,'message'=>$p->nomor_bukti.' · '.$p->status,'created_at'=>now(),'updated_at'=>now()]);
        }, 3);
    }

    public function reason(?string $reason): void
    {
        if (mb_strlen(trim($reason ?? '')) < 5) {
            throw ValidationException::withMessages(['reason' => 'Tuliskan alasan minimal 5 karakter.']);
        }
    }

    public function cancelDocument(int $id, string $reason): void
    {
        DB::transaction(function () use ($id, $reason) {
            $this->reason($reason);
            $doc = Document::where('school_profile_id', auth()->user()->school_profile_id)->findOrFail($id);
            $p = self::purchase($doc->purchase_id, true);
            self::assertEditable($p);
            abort_unless($doc->status === 'terbit', 409);
            $kinds = match ($doc->jenis) {
                'nopes' => ['nopes', 'bapb', 'kuitansi'],'bapb' => ['bapb', 'kuitansi'],default => ['kuitansi']
            };
            $p->documents()->where('status','terbit')->whereIn('jenis',$kinds)->update(['status' => 'dibatalkan']);
            self::event('batalkan_dokumen',$p,$reason,['document_id' => $id, 'jenis_terdampak' => $kinds]);
        }, 3);
    }
}
