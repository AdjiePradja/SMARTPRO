<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks non-active accounts (pending/rejected) from the application and
 * routes them to the "waiting for approval" page (roadmap Task 1.5).
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            return redirect()->route('pending');
        }

        return $next($request);
    }
}
