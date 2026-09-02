<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the SaaS-owner ("super admin") area.
 *
 * Protects every /admin route behind an email + password session login
 * (guard: admin). Unauthenticated visitors are redirected to the login
 * screen; JSON requests get a 401. The first admin account is created by
 * `php artisan db:seed --class=AdminSeeder` (or `demo:seed`), using
 * config('admin.*') / ADMIN_EMAIL / ADMIN_PASSWORD.
 */
class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $authed = Auth::guard('admin')->check();
        } catch (\Throwable $e) {
            // admins table missing (migrations not run yet) — route to the
            // login screen, which explains exactly what to run.
            Log::warning('Admin guard unavailable: '.$e->getMessage());

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Owner accounts are not set up yet. Run: php artisan migrate && php artisan db:seed --class=AdminSeeder',
            ]);
        }

        if (! $authed) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated', 'code' => 'ADMIN_AUTH_REQUIRED'], 401);
            }
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
