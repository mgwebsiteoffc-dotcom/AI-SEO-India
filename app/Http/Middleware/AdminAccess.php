<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the SaaS-owner ("super admin") area.
 *
 * Session-based auth: redirects to /admin/login if not authenticated.
 * Falls back to HTTP Basic when no session exists (API/curl access).
 */
class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Already logged in via session
        if (session('admin_logged_in')) {
            return $next($request);
        }

        $email = (string) config('admin.email', env('ADMIN_EMAIL', ''));
        $password = (string) config('admin.password', env('ADMIN_PASSWORD', ''));

        // If no credentials configured, allow in non-production for preview
        if ($email === '' && $password === '' && ! app()->environment('production')) {
            return $next($request);
        }

        // If credentials are configured but not set, block
        if ($email === '' || $password === '') {
            abort(503, 'Admin access is not configured. Set ADMIN_EMAIL and ADMIN_PASSWORD in .env.');
        }

        // HTTP Basic fallback (for curl/API)
        $user = $request->getUser() ?? '';
        $pass = $request->getPassword() ?? '';
        if ($user !== '' && hash_equals($email, $user) && hash_equals($password, $pass)) {
            return $next($request);
        }

        // Not authenticated — redirect to login page
        if ($request->is('admin/login') || $request->is('admin/login/*')) {
            return $next($request);
        }

        return redirect()->route('admin.login');
    }
}
