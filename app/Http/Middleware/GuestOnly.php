<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GuestOnly
{
    // only guest accounts can open the booking pages; staff have their own sides
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->role != 'guest') {
            return redirect($request->user()->role == 'admin' ? '/admin' : '/dashboard');
        }

        return $next($request);
    }
}
