<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\V3Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends SchoolController
{
    public function accounts()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('v3.accounts', ['users' => User::where('school_profile_id', $this->sid())->where('is_system_admin', false)->get()]);
    }

    public function accountSave(Request $r)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $d = $r->validate(['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:8|confirmed', 'role' => 'required|in:admin,bendahara,kepala_sekolah,user']);
        DB::transaction(function () use ($d) {
            $u = new User;
            $u->forceFill($d + ['school_profile_id' => $this->sid(), 'is_active' => true]);
            unset($u->password_confirmation);
            $u->save();
            V3Workflow::event('buat_akun', null, null, ['id' => $u->id, 'role' => $u->role]);
        });

        return back()->with('success', 'Akun dibuat.');
    }

    public function accountUpdate(Request $r, int $id)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $d = $r->validate(['role' => 'required|in:admin,bendahara,kepala_sekolah,user', 'is_active' => 'required|boolean', 'password' => 'nullable|string|min:8|confirmed']);
        abort_if($id === auth()->id(), 422, 'Perubahan peran/status akun sendiri tidak diperbolehkan.');
        DB::transaction(function () use ($d, $id) {
            $u = User::where('school_profile_id', $this->sid())->where('is_system_admin', false)->lockForUpdate()->findOrFail($id);
            $u->role = $d['role'];
            $u->is_active = $d['is_active'];
            if (! empty($d['password'])) {
                $u->password = $d['password'];
            }
            $u->save();
            V3Workflow::event('ubah_akun', null, null, ['id' => $id, 'role' => $u->role, 'is_active' => $u->is_active]);
        });

        return back()->with('success', 'Akun diperbarui.');
    }
}
