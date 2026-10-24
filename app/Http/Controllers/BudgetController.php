<?php

namespace App\Http\Controllers;

use App\Models\SchoolProfile;
use App\Models\V3\Activity;
use App\Models\V3\Budget;
use App\Models\V3\Purchase;
use App\Services\V3Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BudgetController extends SchoolController
{
    public function budgets()
    {
        return view('v3.budgets', ['budgets' => Budget::where('school_profile_id', $this->sid())->orderByDesc('tahun')->get()]);
    }

    public function budgetSave(Request $r)
    {
        $this->editor();
        $d = $r->validate(['tahun' => 'required|integer|between:2000,2100', 'sumber_dana' => 'required|string|max:100', 'nilai' => 'required|numeric|min:0|max:99999999999999|decimal:0,2']);
        DB::transaction(function () use ($d) {
            SchoolProfile::whereKey($this->sid())->lockForUpdate()->firstOrFail();
            $b = Budget::firstOrNew(['school_profile_id' => $this->sid(), 'tahun' => $d['tahun'], 'sumber_dana' => $d['sumber_dana']]);
            $used = $b->exists ? Purchase::whereHas('activity', fn ($q) => $q->where('budget_id', $b->id))->where('status', '!=', 'dibatalkan')->select('id', 'bruto')->lazyById(500)->pluck('bruto')->reduce(fn ($c, $v) => bcadd($c, (string) $v, 2), '0') : '0';
            if (bccomp((string) $d['nilai'], $used, 2) < 0) {
                throw ValidationException::withMessages(['nilai' => 'Anggaran tidak boleh kurang dari belanja yang sudah dicadangkan.']);
            }
            $before = $b->toArray();
            $b->nilai = $d['nilai'];
            $b->save();
            V3Workflow::event('simpan_anggaran', null, null, ['before' => $before, 'after' => $b->toArray()]);
        }, 3);

        return back()->with('success', 'Anggaran tersimpan.');
    }

    public function budgetEdit(int $id)
    {
        $this->editor();
        $b = Budget::where('school_profile_id', $this->sid())->findOrFail($id);
        $hasPurchases = Purchase::whereHas('activity', fn ($q) => $q->where('budget_id', $b->id))->exists();

        return view('v3.budget-edit', compact('b', 'hasPurchases'));
    }

    public function budgetUpdate(Request $r, int $id)
    {
        $this->editor();
        DB::transaction(function () use ($r, $id) {
            SchoolProfile::whereKey($this->sid())->lockForUpdate()->firstOrFail();
            $b = Budget::where('school_profile_id', $this->sid())->lockForUpdate()->findOrFail($id);
            $d = $r->validate(['tahun' => 'required|integer|between:2000,2100', 'sumber_dana' => ['required', 'string', 'max:100', Rule::unique('v3_budgets', 'sumber_dana')->where(fn ($q) => $q->where('school_profile_id', $this->sid())->where('tahun', $r->input('tahun')))->ignore($b->id)], 'nilai' => 'required|numeric|min:0|max:99999999999999|decimal:0,2']);
            $purchases = Purchase::whereHas('activity', fn ($q) => $q->where('budget_id', $b->id));
            if ((clone $purchases)->exists() && ((int) $d['tahun'] !== (int) $b->tahun || $d['sumber_dana'] !== $b->sumber_dana)) {
                throw ValidationException::withMessages(['tahun' => 'Tahun dan sumber dana tidak dapat diubah karena anggaran sudah memiliki transaksi. Nilai anggaran tetap dapat diperbarui.']);
            }
            $used = $purchases->where('status', '!=', 'dibatalkan')->select('id', 'bruto')->lazyById(500)->pluck('bruto')->reduce(fn ($c, $v) => bcadd($c, (string) $v, 2), '0');
            if (bccomp((string) $d['nilai'], $used, 2) < 0) {
                throw ValidationException::withMessages(['nilai' => 'Anggaran tidak boleh kurang dari belanja yang sudah dicadangkan.']);
            }
            $before = $b->toArray();
            $b->update($d);
            V3Workflow::event('ubah_anggaran', null, null, ['before' => $before, 'after' => $b->toArray()]);
        }, 3);

        return redirect()->route('v3.budgets')->with('success', 'Anggaran berhasil diperbarui.');
    }

    public function budgetDestroy(Request $r, int $id)
    {
        $this->editor();
        DB::transaction(function () use ($r, $id) {
            SchoolProfile::whereKey($this->sid())->lockForUpdate()->firstOrFail();
            $b = Budget::where('school_profile_id', $this->sid())->lockForUpdate()->findOrFail($id);
            $d = $r->validate(['reason' => 'required|string|min:5|max:2000']);
            app(V3Workflow::class)->reason($d['reason']);
            if (Activity::where('budget_id', $b->id)->exists()) {
                throw ValidationException::withMessages(['anggaran' => 'Anggaran tidak dapat dihapus karena sudah digunakan oleh kegiatan.']);
            }
            $before = $b->toArray();
            $b->delete();
            V3Workflow::event('hapus_anggaran', null, $d['reason'], ['before' => $before]);
        }, 3);

        return redirect()->route('v3.budgets')->with('success', 'Anggaran berhasil dihapus.');
    }
}
