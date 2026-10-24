<?php

namespace App\Http\Controllers;

use App\Services\InitialSetup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class SetupController extends Controller
{
    public function create(InitialSetup $setup)
    {
        if (! $setup->needed()) {
            return redirect()->route('login')->with('status', 'Pengaturan awal tidak tersedia. Gunakan akun yang sudah ada.');
        }

        return view('setup.create');
    }

    public function store(Request $request, InitialSetup $setup)
    {
        abort_unless($setup->needed(), 403, 'Pengaturan awal sudah ditutup.');
        $data = $request->validate([
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'npsn' => ['required', 'digits:8'],
            'alamat' => ['required', 'string', 'max:2000'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $user = $setup->complete($data);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('account_version', (int)$request->user()->session_version);

        return redirect()->route('admin.users.create')->with('success', 'Sekolah dan admin siap. Sekarang tambahkan akun bendahara dan kepala sekolah.');
    }
}
