<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    // only the admin and the super admin can open the setup pages; reception has their own side
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()->canManageSetup()) {
            return redirect('/dashboard')->with('error', 'Only the admin can open that page.');
        }

        return $next($request);
    }
}
