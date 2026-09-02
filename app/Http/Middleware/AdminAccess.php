<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the SaaS-owner ("super admin") area.
 *
 *  - Local preview / demo sandbox (no ADMIN_* credentials configured and not
 *    in production): open, so the panel can be previewed without secrets.
 *  - Otherwise HTTP Basic auth against ADMIN_EMAIL + ADMIN_PASSWORD.
 *  - Never opens in production without configured credentials.
 */
class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $email = (string) config('admin.email', env('ADMIN_EMAIL', ''));
        $password = (string) config('admin.password', env('ADMIN_PASSWORD', ''));
        $isProduction = app()->environment('production');

        // Demo/local bypass only when no credentials are set.
        if ($email === '' && $password === '' && ! $isProduction) {
            return $next($request);
        }

        if ($email === '' || $password === '') {
            abort(503, 'Admin access is not configured. Set ADMIN_EMAIL and ADMIN_PASSWORD in .env.');
        }

        $user = $request->getUser() ?? '';
        $pass = $request->getPassword() ?? '';
        if (hash_equals($email, $user) && hash_equals($password, $pass)) {
            return $next($request);
        }

        Log::warning('Admin auth failed from '.$request->ip());

        return response('Admin authentication required.', 401, [
            'WWW-Authenticate' => 'Basic realm="AI Visibility owner area", charset="UTF-8"',
        ]);
    }
}
