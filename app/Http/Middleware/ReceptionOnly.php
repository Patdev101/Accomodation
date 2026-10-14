<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ReceptionOnly
{
    // only reception and the super admin can open the front-desk pages; the admin has their own side
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()->canUseFrontDesk()) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
