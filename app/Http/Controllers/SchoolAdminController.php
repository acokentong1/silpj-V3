<?php

namespace App\Http\Controllers;

use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolAdminController extends Controller
{
    private function authorizeSystem(): void
    {
        abort_unless(auth()->user()->isSystemAdmin(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeSystem();
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:active,inactive']);
        $query = SchoolProfile::query()->withCount(['users' => fn ($q) => $q->where('is_system_admin', false)]);
        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('nama_sekolah', 'like', '%'.$search.'%')->orWhere('npsn', 'like', '%'.$search.'%'));
        }
        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        $total = SchoolProfile::count();
        $active = SchoolProfile::where('is_active', true)->count();

        return view('v3.schools', [
            'schools' => $query->orderBy('nama_sekolah')->paginate(15)->withQueryString(),
            'stats' => ['total' => $total, 'active' => $active, 'inactive' => $total - $active],
            'search' => $search, 'status' => $filters['status'] ?? '',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSystem();
        $data = $request->validate([
            'nama_sekolah' => 'required|string|max:255', 'npsn' => 'required|digits:8|unique:school_profiles,npsn',
            'alamat' => 'required|string|max:2000', 'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|confirmed',
        ]);
        DB::transaction(function () use ($data) {
            $school = SchoolProfile::create(['nama_sekolah' => $data['nama_sekolah'], 'npsn' => $data['npsn'], 'alamat' => $data['alamat'], 'is_active' => true]);
            $admin = new User;
            $admin->forceFill(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
                'role' => 'admin', 'school_profile_id' => $school->id, 'is_active' => true, 'is_system_admin' => false])->save();
            DB::table('system_events')->insert(['actor_id' => auth()->id(), 'school_profile_id' => $school->id, 'action' => 'buat_sekolah',
                'details' => json_encode(['admin_id' => $admin->id]), 'created_at' => now(), 'updated_at' => now()]);
        }, 3);

        return redirect()->route('system.schools')->with('success', 'Sekolah dan admin sekolah berhasil dibuat. Admin sekolah dapat masuk dan melengkapi pengaturan.');
    }

    public function update(Request $request, int $school)
    {
        $this->authorizeSystem();
        $data = $request->validate(['is_active' => 'required|boolean', 'reason' => 'required|string|min:5|max:2000']);
        DB::transaction(function () use ($data, $school) {
            $s = SchoolProfile::lockForUpdate()->findOrFail($school);
            $before = (bool) $s->is_active;
            $s->is_active = (bool) $data['is_active'];
            $s->save();
            DB::table('system_events')->insert(['actor_id' => auth()->id(), 'school_profile_id' => $s->id, 'action' => 'status_sekolah',
                'details' => json_encode(['before' => $before, 'after' => $s->is_active, 'reason' => $data['reason']]), 'created_at' => now(), 'updated_at' => now()]);
        }, 3);

        return back()->with('success','Status sekolah diperbarui.');
    }
}
