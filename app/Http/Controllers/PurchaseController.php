<?php

namespace App\Http\Controllers;

use App\Models\V3\Activity;
use App\Services\V3Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PurchaseController extends SchoolController
{
    public function form(int $activity, ?int $id = null)
    {
        $this->editor();
        $a = Activity::where('school_profile_id', $this->sid())->with('budget')->findOrFail($activity);
        $p = $id ? V3Workflow::purchase($id) : null;
        if ($p) {
            V3Workflow::assertEditable($p);
            abort_unless($p->activity_id === $a->id, 404);
            $p->load('items', 'components', 'vendor');
        }

        $vendorOptions = \App\Models\V3\Vendor::where('school_profile_id',$this->sid())->orderByDesc('id')->limit(200)->get()->unique(fn($v)=>$v->nama.'|'.$v->npwp);
        return view('v3.form', compact('a', 'p','vendorOptions'));
    }

    public function duplicate(int $id)
    {
        $this->editor();
        $source = V3Workflow::purchase($id)->load('items', 'components', 'vendor', 'activity');
        abort_if((bool) $source->activity->is_archived, 422, 'Kegiatan sudah diarsipkan.');
        $data = [
            'nomor_bukti' => '', 'tanggal' => '', 'kategori' => $source->kategori,
            'vendor_nama' => $source->vendor->nama, 'vendor_alamat' => $source->vendor->alamat,
            'vendor_penerima' => $source->vendor->penerima, 'vendor_npwp' => $source->vendor->npwp,
            'items' => $source->items->map(fn ($i) => $i->only(['nama','volume','satuan','harga']))->all(),
            'components' => $source->components->map(fn ($c) => $c->only(['label','kategori','metode','persentase','dasar','nominal','perlakuan']))->all(),
        ];
        return redirect()->route('v3.create', $source->activity_id)->withInput($data)
            ->with('success', 'Rincian disalin ke formulir baru. Isi nomor bukti dan tanggal, periksa harga serta pajak, lalu simpan. Bukti, penerimaan, dokumen dan persetujuan tidak disalin.');
    }

    public function preview(int $id)
    {
        $f = DB::table('v3_attachments')->find($id);
        abort_unless($f, 404);
        V3Workflow::purchase($f->purchase_id);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($f->path), 404, 'Berkas bukti tidak ditemukan.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($disk->path($f->path));
        abort_unless(in_array($mime, ['application/pdf','image/jpeg','image/png'], true), 415);
        abort_unless(hash_equals($f->sha256, hash_file('sha256', $disk->path($f->path))), 409, 'Berkas berubah. Periksa bukti asli.');
        return $disk->response($f->path, 'bukti.'.$id.'.'.(['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'][$mime]), [
            'Content-Type'=>$mime, 'X-Content-Type-Options'=>'nosniff', 'Cache-Control'=>'private, no-store',
            'Content-Security-Policy'=>"default-src 'none'; frame-ancestors 'self'", 'X-Frame-Options'=>'SAMEORIGIN',
        ], 'inline');
    }

    private function data(Request $r, bool $edit): array
    {
        $d = $r->validate(['expected_revision' => ($edit ? 'required' : 'nullable').'|integer|min:1', 'nomor_bukti' => 'required|string|max:100', 'tanggal' => 'required|date_format:Y-m-d', 'kategori' => 'required|in:Barang,Jasa', 'vendor_nama' => 'required|string|max:255', 'vendor_alamat' => 'nullable|string|max:1000', 'vendor_penerima' => 'required|string|max:255', 'vendor_npwp' => 'nullable|string|max:50', 'reason' => 'nullable|string|min:5|max:2000',
            'items' => 'required|array|min:1|max:200', 'items.*.nama' => 'required|string|max:255', 'items.*.volume' => 'required|numeric|min:0.001|max:1000000|decimal:0,3', 'items.*.satuan' => 'required|string|max:40', 'items.*.harga' => ['required', 'numeric', 'min:0', 'max:1000000000', new \App\Rules\WholeRupiah],
            'components' => 'nullable|array|max:30', 'components.*.label' => 'required|string|max:60', 'components.*.kategori' => 'required|in:pajak,lainnya', 'components.*.metode' => 'required|in:nominal,persentase', 'components.*.persentase' => 'nullable|numeric|min:0|max:100|decimal:0,4', 'components.*.dasar' => ['nullable', 'numeric', 'min:0', 'max:99999999999999', new \App\Rules\WholeRupiah], 'components.*.nominal' => ['nullable', 'numeric', 'min:0', 'max:99999999999999', new \App\Rules\WholeRupiah], 'components.*.perlakuan' => 'required|in:tambah,potong,termasuk']);
        foreach ($d['components'] ?? [] as $c) {
            foreach ($c['metode'] === 'persentase' ? ['persentase', 'dasar'] : ['nominal'] as $key) {
                if (! isset($c[$key])) {
                    throw ValidationException::withMessages(['components' => 'Lengkapi nilai komponen sesuai metode yang dipilih.']);
                }
            }
        }

        return $d;
    }

    public function save(Request $r, int $activity, ?int $id = null)
    {
        $this->editor();
        $a = Activity::where('school_profile_id', $this->sid())->with('budget')->findOrFail($activity);
        $d = $this->data($r, (bool) $id);
        if ((int) substr($d['tanggal'], 0, 4) !== $a->budget->tahun) {
            throw ValidationException::withMessages(['tanggal' => 'Tanggal transaksi harus sesuai tahun anggaran.']);
        }
        $p = app(V3Workflow::class)->save($a, $d, $id);

        return redirect()->route('v3.purchase', $p->id)->with('success', 'Transaksi dan seluruh rincian tersimpan.');
    }

    public function purchase(int $id)
    {
        $p = V3Workflow::purchase($id)->load('activity.budget', 'items', 'components', 'vendor', 'documents');

        return view('v3.purchase', ['p' => $p, 'attachments' => DB::table('v3_attachments')->where('purchase_id', $id)->get(), 'events' => DB::table('v3_events')->join('users', 'users.id', '=', 'v3_events.actor_id')->where('purchase_id', $id)->select('v3_events.*', 'users.name')->orderByDesc('v3_events.id')->get()]);
    }

    public function transition(Request $r, int $id)
    {
        $d = $r->validate(['action' => 'required|in:ajukan,setujui,revisi,buka,batalkan', 'reason' => 'nullable|string|max:2000']);
        if ($d['action'] === 'setujui' && $r->user()->isKepalaSekolah()) {
            $r->validate(['confirmation' => 'accepted']);
        }
        app(V3Workflow::class)->transition($id, $d['action'], $d['reason'] ?? null);

        return back()->with('success', 'Status diperbarui.');
    }

    public function upload(Request $r, int $id)
    {
        $this->editor();
        $r->validate(['file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 'category'=>'required|in:nota,pembayaran,penerimaan,pajak,lainnya']);
        $file = $r->file('file');
        $path = $file->store('v3/schools/'.$this->sid().'/bukti', 'local');
        try {
            DB::transaction(function () use ($file, $path, $id, $r) {
                $p = V3Workflow::purchase($id, true);
                V3Workflow::assertEditable($p);
                DB::table('v3_attachments')->insert(['purchase_id' => $id, 'category'=>$r->input('category'), 'revision' => $p->revision, 'is_active' => true, 'name' => $file->getClientOriginalName(), 'path' => $path, 'mime' => $file->getMimeType(), 'sha256' => hash_file('sha256', Storage::disk('local')->path($path)), 'created_at' => now(), 'updated_at' => now()]);
                V3Workflow::event('unggah_bukti', $p);
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return back()->with('success', 'Bukti tersimpan.');
    }

    public function attachment(int $id)
    {
        $f = DB::table('v3_attachments')->find($id);
        abort_unless($f, 404);
        V3Workflow::purchase($f->purchase_id);

        abort_unless(Storage::disk('local')->exists($f->path), 404, 'Berkas bukti tidak ditemukan.');
        abort_unless(\App\Services\LpjReadiness::validFile($f), 409, 'Berkas bukti berubah atau rusak. Periksa bukti asli.');
        return Storage::disk('local')->download($f->path, $f->name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
