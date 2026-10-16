<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureSchoolIsActive {
 public function handle(Request $request, Closure $next) {
  $school=$request->user()?->schoolProfile;
  // Existing sessions and Laravel's guest redirect may still target dashboard.
  // Only redirect this landing page; never bypass authorization on school data.
  if ((!$school || !$school->is_active) && $request->user()?->isSystemAdmin()
      && $request->isMethod('GET') && $request->routeIs('dashboard')) {
   return redirect()->route('system.schools');
  }
  abort_unless($school && $school->is_active,403,'Sekolah tidak aktif atau akun belum terhubung ke sekolah. Hubungi administrator sistem.');
  return $next($request);
 }
}
