<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ReceptionOnly
{
    // only reception can open the front-desk pages; the admin has their own side
    public function handle(Request $request, Closure $next)
    {
        // guest accounts only use the public site
        if ($request->user()->role == 'guest') {
            return redirect('/');
        }

        if ($request->user()->role != 'reception') {
            return redirect('/admin');
        }

        return $next($request);
    }
}
