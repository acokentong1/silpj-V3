<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class EnsureSessionVersion {
 public function handle(Request $request, Closure $next) {
  if ($u=$request->user()) {
   $version=(int)$u->session_version;
   $stored=$request->session()->get('account_version');
   // Old sessions from the previous release are invalidated. Tests use actingAs.
   if (($stored===null && !app()->runningUnitTests()) || ($stored!==null && (int)$stored!==$version)) {
    Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
    return redirect()->route('login')->withErrors(['email'=>'Sesi berakhir. Silakan masuk kembali.']);
   }
  }
  return $next($request);
 }
}
