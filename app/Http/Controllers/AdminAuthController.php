<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/** Email + password login for the SaaS-owner (super admin) panel. */
class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.overview');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $ok = Auth::guard('admin')->attempt($credentials, $request->boolean('remember'));
        } catch (\Throwable $e) {
            Log::error('Super-admin login DB error: '.$e->getMessage());

            return back()->withErrors([
                'email' => 'The admin store is not ready yet. Run: php artisan migrate && php artisan db:seed --class=AdminSeeder',
            ])->onlyInput('email');
        }

        if ($ok) {
            $request->session()->regenerate();
            Log::info('Super-admin login: '.$credentials['email'].' from '.$request->ip());

            return redirect()->intended(route('admin.overview'));
        }

        Log::warning('Super-admin login failed for '.$credentials['email'].' from '.$request->ip());

        return back()->withErrors(['email' => 'These credentials do not match our records.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
