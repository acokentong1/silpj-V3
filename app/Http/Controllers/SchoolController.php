<?php

namespace App\Http\Controllers;

abstract class SchoolController extends Controller
{
    protected function sid()
    {
        return auth()->user()->school_profile_id ?? abort(403, 'Akun belum terhubung ke sekolah.');
    }

    protected function editor()
    {
        abort_unless(auth()->user()->isBendahara(), 403);
    }
}
