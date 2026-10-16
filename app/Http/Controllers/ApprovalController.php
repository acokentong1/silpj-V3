<?php

namespace App\Http\Controllers;

use App\Models\V3\Purchase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isKepalaSekolah(), 403);
        $schoolId = $request->user()->school_profile_id ?? abort(403);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['diajukan', 'disetujui', 'revisi'])],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? 'diajukan';
        $base = Purchase::where('school_profile_id', $schoolId);
        $years = (clone $base)->distinct()->orderByDesc('tahun')->pluck('tahun');
        if (!empty($filters['tahun'])) {
            $base->where('tahun', $filters['tahun']);
        }
        // Summary is scoped to the school/year, independent of the search term.
        $counts = (clone $base)->selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $query = (clone $base)->where('status', $status)->with('activity.budget', 'vendor');
        if (!empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('nomor_bukti', 'like', $term)
                    ->orWhereHas('activity', fn ($a) => $a->where('nama', 'like', $term))
                    ->orWhereHas('vendor', fn ($v) => $v->where('nama', 'like', $term));
            });
        }
        $purchases = $query->orderBy('id')->paginate(15)->withQueryString();
        return view('v3.approvals', compact('purchases', 'years', 'counts', 'status'));
    }
}
