<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Restrict the route group to users having one of the given roles
     * ("admin", "associate" or "driver").
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // A deactivated associate loses access immediately, even with a live session.
        if ($user->isAssociate() && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated. Please contact the administrator.']);
        }

        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        // Send the user back to the panel that belongs to his role
        // (e.g. an associate can never open the admin panel).
        return redirect()->route($user->homeRouteName());
    }
}
