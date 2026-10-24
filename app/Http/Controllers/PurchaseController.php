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

        return view('v3.form', compact('a', 'p'));
    }

    private function data(Request $r, bool $edit): array
    {
        $d = $r->validate(['nomor_bukti' => 'required|string|max:100', 'tanggal' => 'required|date_format:Y-m-d', 'kategori' => 'required|in:Barang,Jasa', 'vendor_nama' => 'required|string|max:255', 'vendor_alamat' => 'nullable|string|max:1000', 'vendor_penerima' => 'required|string|max:255', 'vendor_npwp' => 'nullable|string|max:50', 'reason' => ($edit ? 'required' : 'nullable').'|string|min:5|max:2000',
            'items' => 'required|array|min:1|max:200', 'items.*.nama' => 'required|string|max:255', 'items.*.volume' => 'required|numeric|min:0.001|max:1000000|decimal:0,3', 'items.*.satuan' => 'required|string|max:40', 'items.*.harga' => 'required|numeric|min:0|max:1000000000|decimal:0,2',
            'components' => 'nullable|array|max:30', 'components.*.label' => 'required|string|max:60', 'components.*.kategori' => 'required|in:pajak,lainnya', 'components.*.metode' => 'required|in:nominal,persentase', 'components.*.persentase' => 'nullable|numeric|min:0|max:100|decimal:0,4', 'components.*.dasar' => 'nullable|numeric|min:0|max:99999999999999|decimal:0,2', 'components.*.nominal' => 'nullable|numeric|min:0|max:99999999999999|decimal:0,2', 'components.*.perlakuan' => 'required|in:tambah,potong,termasuk']);
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
        $r->validate(['file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $file = $r->file('file');
        $path = $file->store('v3/schools/'.$this->sid().'/bukti', 'local');
        try {
            DB::transaction(function () use ($file, $path, $id) {
                $p = V3Workflow::purchase($id, true);
                V3Workflow::assertEditable($p);
                DB::table('v3_attachments')->insert(['purchase_id' => $id, 'name' => $file->getClientOriginalName(), 'path' => $path, 'mime' => $file->getMimeType(), 'sha256' => hash_file('sha256', Storage::disk('local')->path($path)), 'created_at' => now(), 'updated_at' => now()]);
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

        return Storage::disk('local')->download($f->path, $f->name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
