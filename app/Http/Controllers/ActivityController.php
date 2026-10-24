<?php

namespace App\Http\Controllers;

use App\Models\V3\Activity;
use App\Models\V3\Document;
use App\Services\V3Workflow;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ActivityController extends SchoolController
{
    public function activityStore(Request $r)
    {
        $this->editor();
        $d = $r->validate(['nama' => 'required|string|max:255', 'budget_id' => ['required', Rule::exists('v3_budgets', 'id')->where('school_profile_id', $this->sid())], 'keterangan' => 'nullable|string|max:2000']);
        $a = DB::transaction(function () use ($d) {
            $a = Activity::create($d + ['school_profile_id' => $this->sid(), 'created_by' => auth()->id()]);
            V3Workflow::event('buat_kegiatan', null, null, ['id' => $a->id, 'nama' => $a->nama]);

            return $a;
        });

        return redirect()->route('v3.activity', $a->id)->with('success', 'Kegiatan dibuat. Tambahkan transaksi belanja.');
    }

    public function activity(Request $r, int $id)
    {
        $a = Activity::where('school_profile_id', $this->sid())->with('budget')->findOrFail($id);

        $d=$r->validate(['q'=>'nullable|string|max:100','show_cancelled'=>'nullable|boolean']);
        $query=$a->purchases()->with('vendor');
        if(!$r->boolean('show_cancelled')) $query->where('status','!=','dibatalkan');
        if(!empty($d['q'])) $query->where(fn($q)=>$q->where('nomor_bukti','like','%'.$d['q'].'%')->orWhereHas('vendor',fn($v)=>$v->where('nama','like','%'.$d['q'].'%')));
        return view('v3.activity', ['a' => $a, 'purchases' => $query->latest()->paginate(20)->withQueryString()]);
    }

    public function activityDocuments(int $id)
    {
        $a = Activity::where('school_profile_id', $this->sid())->findOrFail($id);
        $documents = Document::query()
            ->join('v3_purchases', 'v3_purchases.id', '=', 'v3_documents.purchase_id')
            ->where('v3_purchases.activity_id', $a->id)
            ->where('v3_purchases.school_profile_id', $this->sid())
            ->where('v3_documents.school_profile_id', $this->sid())
            ->where('v3_purchases.status', '!=', 'dibatalkan')
            ->where('v3_documents.status', 'terbit')
            ->whereColumn('v3_documents.revision', 'v3_purchases.revision')
            ->whereIn('v3_documents.jenis', ['nopes', 'bapb', 'kuitansi'])
            ->orderBy('v3_purchases.tanggal')->orderBy('v3_purchases.id')
            ->orderByRaw("CASE v3_documents.jenis WHEN 'nopes' THEN 1 WHEN 'bapb' THEN 2 WHEN 'kuitansi' THEN 3 END")
            ->select('v3_documents.*')->get();
        if ($documents->isEmpty()) {
            return redirect()->route('v3.activity', $a->id)->withErrors(['dokumen' => 'Belum ada dokumen aktif yang dapat diunduh. Terbitkan Nopes, Berita Acara, atau Kuitansi pada transaksi kegiatan ini terlebih dahulu.']);
        }

        return Pdf::loadView('v3.print-activity', compact('a', 'documents'))->setPaper('a4')->download('dokumen-kegiatan-'.$a->id.'.pdf');
    }
}
