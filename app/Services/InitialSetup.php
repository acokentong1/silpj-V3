<?php

namespace App\Services;

use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InitialSetup
{
    public function needed(): bool
    {
        // Missing migration never enables public administrator creation.
        if (!Schema::hasTable('installation_state')) {
            return false;
        }
        $state = DB::table('installation_state')->where('id', 1)->first();
        return $state && !$state->completed && !User::exists() && !SchoolProfile::exists();
    }

    public function complete(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Lock an existing singleton row: two concurrent requests cannot create two admins.
            $state = DB::table('installation_state')->where('id', 1)->lockForUpdate()->first();
            abort_unless($state && !$state->completed && !User::exists() && !SchoolProfile::exists(), 403, 'Pengaturan awal sudah ditutup.');
            $school = SchoolProfile::create([
                'nama_sekolah' => $data['nama_sekolah'],
                'npsn' => $data['npsn'],
                'alamat' => $data['alamat'],
            ]);
            $user = new User();
            $user->forceFill([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => $data['password'], 'role' => 'admin',
                'is_active' => true, 'school_profile_id' => $school->id,
            ])->save();
            DB::table('installation_state')->where('id', 1)->update(['completed' => true]);
            DB::table('v3_events')->insert(['school_profile_id'=>$school->id,'actor_id'=>$user->id,'action'=>'pengaturan_awal','created_at'=>now(),'updated_at'=>now()]);
            return $user;
        }, 3);
    }
}
