<?php

namespace App\Http\Controllers;

use App\Models\V3\Activity;
use App\Models\V3\Budget;
use App\Models\V3\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class V3Controller extends SchoolController
{
    public function index(Request $r)
    {
        if ($r->user()->role === 'kepala_sekolah') {
            return redirect()->route('v3.approvals');
        }
        $r->validate(['show_archived'=>'nullable|boolean']);
        $years = Budget::where('school_profile_id', $this->sid())->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn ($year) => (int) $year);
        if ($years->isEmpty()) {
            $years = collect([(int) date('Y')]);
        }
        $yearKey = 'dashboard_year_'.$this->sid();
        $defaultYear = $years->contains((int) date('Y')) ? (int) date('Y') : $years->first();
        if ($r->has('tahun')) {
            $r->validate(['tahun' => ['required', 'integer', Rule::in($years->all())]]);
            $selectedYear = (int) $r->input('tahun');
        } else {
            $selectedYear = (int) $r->session()->get($yearKey, $defaultYear);
            if (! $years->contains($selectedYear)) {
                $selectedYear = $defaultYear;
            }
        }
        $r->session()->put($yearKey, $selectedYear);

        $q = Activity::where('school_profile_id', $this->sid())->whereHas('budget', fn ($q) => $q->where('tahun', $selectedYear))->with('budget')->withCount('purchases')->withSum(['purchases' => fn ($q) => $q->where('status', '!=', 'dibatalkan')], 'bruto');
        if(!$r->boolean('show_archived')) $q->where('is_archived',false);
        if ($r->filled('q')) {
            $q->where('nama', 'like', '%'.mb_substr($r->input('q'), 0, 100).'%');
        }
        $budgets = Budget::where('school_profile_id', $this->sid())->where('tahun', $selectedYear)->orderByDesc('tahun')->orderBy('sumber_dana')->get();
        $usage = [];
        DB::table('v3_purchases as p')->join('v3_activities as a', 'a.id', '=', 'p.activity_id')
            ->where('p.school_profile_id', $this->sid())->where('a.school_profile_id', $this->sid())
            ->whereIn('a.budget_id', $budgets->pluck('id'))->where('p.status', '!=', 'dibatalkan')
            ->select('p.id', 'a.budget_id', 'p.bruto')->lazyById(500, 'p.id', 'id')->each(function ($row) use (&$usage) {
                $usage[$row->budget_id] = bcadd($usage[$row->budget_id] ?? '0', (string) $row->bruto, 2);
            });
        $budgetSummary = ['total' => '0.00', 'used' => '0.00'];
        foreach ($budgets as $budget) {
            $used = ($usage[$budget->id] ?? '0.00');
            $budget->used = $used;
            $budget->remaining = bcsub((string) $budget->nilai, $used, 2);
            $budget->percentage = bccomp((string) $budget->nilai, '0', 2) > 0 ? bcdiv(bcmul($used, '100', 4), (string) $budget->nilai, 2) : null;
            $budgetSummary['total'] = bcadd($budgetSummary['total'], (string) $budget->nilai, 2);
            $budgetSummary['used'] = bcadd($budgetSummary['used'], $used, 2);
        }
        $budgetSummary['remaining'] = bcsub($budgetSummary['total'], $budgetSummary['used'], 2);
        $budgetSummary['percentage'] = bccomp($budgetSummary['total'], '0', 2) > 0 ? bcdiv(bcmul($budgetSummary['used'], '100', 4), $budgetSummary['total'], 2) : null;

        return view('v3.index', ['activities' => $q->latest()->paginate(15)->appends(['tahun' => $selectedYear, 'q' => $r->input('q'), 'show_archived'=>$r->boolean('show_archived')?1:0]), 'years' => $years, 'selectedYear' => $selectedYear, 'budgets' => $budgets, 'budgetSummary' => $budgetSummary, 'counts' => Purchase::where('school_profile_id', $this->sid())->whereHas('activity.budget',fn ($q) => $q->where('tahun',$selectedYear))->selectRaw('status,count(*) as total')->groupBy('status')->pluck('total','status')]);
    }
}
