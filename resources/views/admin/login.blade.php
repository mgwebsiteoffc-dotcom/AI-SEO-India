<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => 'Admin Login', 'description' => 'Super admin login'])
</head>
<body class="marketing min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-500 to-sky-400 flex items-center justify-center text-white text-lg font-extrabold mx-auto mb-3">AI</div>
            <h1 class="font-display text-xl font-extrabold text-white">Super Admin</h1>
            <p class="text-xs text-slate-500 mt-1">AI Visibility — SaaS Owner Panel</p>
        </div>

        <form method="POST" action="{{ route('admin.login.submit') }}" class="glass rounded-2xl p-6 space-y-4">
            @csrf
            @if ($errors->any())
                <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-3 text-xs text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif
            <div>
                <label class="text-xs font-semibold text-slate-300">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" autofocus
                       class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                       placeholder="admin@yourdomain.com" />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-300">Password</label>
                <input type="password" name="password"
                       class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                       placeholder="••••••••" />
            </div>
            <button type="submit" class="btn-primary w-full">Sign in</button>
        </form>

        <p class="text-center text-[11px] text-slate-600 mt-6">
            Set ADMIN_EMAIL and ADMIN_PASSWORD in .env
        </p>
    </div>
</body>
</html>
