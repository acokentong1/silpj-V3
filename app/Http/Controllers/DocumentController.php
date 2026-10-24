<?php

namespace App\Http\Controllers;

use App\Models\V3\Document;
use App\Services\DocumentAssets;
use App\Services\V3Workflow;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends SchoolController
{
    public function issue(Request $r, int $id)
    {
        $this->editor();
        $d = $r->validate(['jenis' => 'required|in:nopes,bapb,kuitansi', 'tanggal' => 'required|date_format:Y-m-d', 'tanggal_lunas' => 'nullable|required_if:jenis,kuitansi|date_format:Y-m-d', 'catatan' => 'nullable|string|max:2000', 'inspection' => 'nullable|required_if:jenis,bapb|array|max:200', 'inspection.*.baik' => 'required|numeric|min:0|max:1000000|decimal:0,3', 'inspection.*.rusak' => 'required|numeric|min:0|max:1000000|decimal:0,3', 'inspection.*.catatan' => 'nullable|string|max:255']);
        $doc = app(V3Workflow::class)->issue($id, $d);

        return redirect()->route('v3.document', $doc->id);
    }

    public function document(Request $r, int $id)
    {
        $doc = Document::where('school_profile_id', $this->sid())->findOrFail($id);
        $s = DocumentAssets::hydrate($doc->snapshot, $doc->school_profile_id);
        $live = \App\Models\V3\Purchase::findOrFail($doc->purchase_id);
        $reviewStatus = $live->status;
        $approvalTime = $live->approved_at;
        $data = compact('doc', 's', 'reviewStatus', 'approvalTime');
        if ($r->boolean('pdf')) {
            return Pdf::loadView('v3.print-v1', $data)->setPaper('a4')->download($doc->jenis.'-'.$doc->id.'.pdf');
        }

return view('v3.print-v1', $data);
    }

    public function cancelDocument(Request $r, int $id)
    {
        $d = $r->validate(['reason' => 'required|string|min:5|max:2000']);
        app(V3Workflow::class)->cancelDocument($id, $d['reason']);

        return back()->with('success','Dokumen dibatalkan; dokumen turunannya juga dibatalkan.');
    }
}
