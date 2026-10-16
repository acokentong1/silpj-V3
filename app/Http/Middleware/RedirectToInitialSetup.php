<?php

namespace App\Http\Middleware;

use App\Services\InitialSetup;
use Closure;
use Illuminate\Http\Request;

class RedirectToInitialSetup
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('/', 'login', 'register') && app(InitialSetup::class)->needed()) {
            return redirect()->route('setup.create');
        }
        return $next($request);
    }
}
