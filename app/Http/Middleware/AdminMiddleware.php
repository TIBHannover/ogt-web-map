<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()
                ->route("login")
                ->with(
                    "error",
                    "Sie müssen angemeldet sein, um auf den Admin-Bereich zuzugreifen.",
                );
        }

        // Check if user has admin rights - handle both object and boolean properties
        $user = Auth::user();
        $isAdmin = false;

        if (method_exists($user, "isAdmin")) {
            $isAdmin = $user->isAdmin();
        } elseif (
            property_exists($user, "is_admin") ||
            isset($user->is_admin)
        ) {
            $isAdmin = (bool) $user->is_admin;
        }

        if (!$isAdmin) {
            return redirect()
                ->route("home")
                ->with(
                    "error",
                    "Sie haben keine Berechtigung, auf den Admin-Bereich zuzugreifen.",
                );
        }

        return $next($request);
    }
}
