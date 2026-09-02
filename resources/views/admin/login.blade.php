<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Owner Login',
        'description' => 'Sign in to the AI Visibility SaaS owner panel.',
    ])
</head>
<body class="marketing min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4 relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative w-full max-w-md">
            <div class="text-center mb-6">
                <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-500 to-sky-400 items-center justify-center text-white text-xl font-extrabold shadow-xl shadow-brand-500/25">AI</div>
                <h1 class="font-display text-2xl font-extrabold text-white mt-4">SaaS owner login</h1>
                <p class="text-sm text-slate-400 mt-1">AI Visibility — owner panel</p>
            </div>

            <div class="glass rounded-3xl p-6 md:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-sm px-4 py-3">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-red-500/15 border border-red-500/40 text-red-300 text-sm px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                               class="input mt-1" placeholder="owner@aivisibility.app">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Password</label>
                        <input type="password" name="password" required autocomplete="current-password"
                               class="input mt-1" placeholder="••••••••••">
                    </div>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="w-4 h-4 accent-brand-500">
                        <span class="text-xs text-slate-400">Remember me on this device</span>
                    </label>
                    <button type="submit" class="btn-primary w-full justify-center text-sm">Sign in to owner panel</button>
                </form>

                @if (app()->environment() !== 'production' && ! config('admin.hide_demo_hint'))
                    <div class="mt-5 rounded-xl bg-white/[0.04] border border-white/10 px-4 py-3 text-[11px] text-slate-400 leading-relaxed">
                        <b class="text-slate-300">Demo owner account</b><br>
                        {{ config('admin.email') }} · {{ config('admin.password') }}
                    </div>
                @endif
            </div>

            <p class="text-center mt-6">
                <a href="{{ route('home') }}" class="text-xs text-slate-500 hover:text-white transition-colors">← Back to site</a>
            </p>
        </div>
    </div>
</body>
</html>
