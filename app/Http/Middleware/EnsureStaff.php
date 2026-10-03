<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admin endpoints: the authenticated user must hold an admin or editor role (read from the database). */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isStaff(), 403);

        return $next($request);
    }
}
