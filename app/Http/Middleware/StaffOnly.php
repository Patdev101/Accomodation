<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffOnly
{
    // pages that both reception and the admin can open; guest accounts cannot
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->role == 'guest') {
            return redirect('/');
        }

        return $next($request);
    }
}
