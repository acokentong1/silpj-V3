<?php

namespace App\Http\Controllers;

use App\Models\SchoolProfile;
use App\Services\V3Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends SchoolController
{
    public function settings()
    {
        return view('v3.settings', ['s' => SchoolProfile::findOrFail($this->sid())]);
    }

    public function settingsSave(Request $r)
    {
        $this->editor();
        $rules = [];
        foreach (['nama_sekolah', 'nama_pemda', 'nama_dinas', 'tempat_cetak', 'kepala_sekolah', 'bendahara', 'pemeriksa', 'kode_surat', 'kode_sekolah'] as $f) {
            $rules[$f] = 'required|string|max:255';
        }
        foreach (['nip_kepala', 'nip_bendahara', 'nip_pemeriksa'] as $f) {
            $rules[$f] = 'nullable|string|max:50';
        }
        $rules += ['npsn' => ['required', 'digits:8', Rule::unique('school_profiles', 'npsn')->ignore($this->sid())], 'alamat' => 'required|string|max:2000', 'logo_pemda' => 'nullable|image|mimes:png,jpg,jpeg|max:2048|dimensions:max_width=4000,max_height=4000', 'logo_sekolah' => 'nullable|image|mimes:png,jpg,jpeg|max:2048|dimensions:max_width=4000,max_height=4000'];
        $d = $r->validate($rules);
        $paths = [];
        foreach (['logo_pemda', 'logo_sekolah'] as $f) {
            unset($d[$f]);
            if ($r->hasFile($f)) {
                $d[$f] = $r->file($f)->store('v3/schools/'.$this->sid().'/logos', 'local');
                $paths[] = $d[$f];
            }
        }
        try {
            DB::transaction(function () use ($d) {
                $s = SchoolProfile::whereKey($this->sid())->lockForUpdate()->firstOrFail();
                $before = $s->toArray();
                $s->update($d);
                V3Workflow::event('ubah_pengaturan', null, null, ['before' => $before, 'after' => $s->toArray()]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($paths);
            throw $e;
        }

        return back()->with('success', 'Pengaturan tersimpan. Dokumen yang sudah terbit tetap memakai data saat diterbitkan.');
    }

    public function logo(string $kind)
    {
        abort_unless(in_array($kind, ['logo_pemda', 'logo_sekolah']), 404);
        $s = SchoolProfile::findOrFail($this->sid());
        abort_unless($s->$kind && Storage::disk('local')->exists($s->$kind), 404);

        return response()->file(Storage::disk('local')->path($s->$kind));
    }
}
