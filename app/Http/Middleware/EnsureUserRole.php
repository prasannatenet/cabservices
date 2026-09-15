<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Restrict the route group to users having the given role ("admin" or "driver").
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($role === 'admin' && ! $user->isAdmin()) {
            return redirect()->route('driver.dashboard');
        }

        if ($role === 'driver' && ! $user->isDriver()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
