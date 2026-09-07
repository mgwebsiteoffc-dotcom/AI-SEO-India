<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => 'AI Settings', 'description' => 'Configure LLM providers for AI tracking and content generation.'])
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'settings'])
    <main class="max-w-3xl mx-auto px-4 py-8 space-y-8">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-white">AI / LLM Settings</h1>
            <p class="text-sm text-slate-400 mt-1">Configure which LLM provider powers AI tracking, content generation and Smart Blogger.</p>
        </div>

        @if (session('status'))
            <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-sm text-emerald-300">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-4 text-sm text-red-300">{{ session('error') }}</div>
        @endif

        <!-- Current status -->
        <div class="glass rounded-2xl p-5">
            <div class="text-sm font-bold text-white mb-2">Current status</div>
            <div class="flex items-center gap-3">
                @if ($llmAvailable)
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-500/20 text-emerald-300">Active</span>
                    <span class="text-sm text-slate-300">Provider: <b class="text-white">{{ ucfirst($activeProvider) }}</b></span>
                @else
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-500/20 text-amber-300">No LLM key configured</span>
                    <span class="text-sm text-slate-400">Using retrieval proxy mode (DuckDuckGo) for tracking. Template mode for content.</span>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.save') }}" class="space-y-6">
            @csrf

            <!-- OpenRouter (recommended) -->
            <div class="glass rounded-2xl p-5">
                <div class="flex items-center gap-2 mb-1">
                    <div class="text-sm font-bold text-white">OpenRouter</div>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-brand-500/20 text-brand-300">Recommended</span>
                </div>
                <p class="text-xs text-slate-400 mb-4">Access 200+ models via a single API key. Free models available (e.g. nvidia/nemotron-3.5-lightning:free). Get your key at <a href="https://openrouter.ai/keys" target="_blank" class="text-brand-400 hover:underline">openrouter.ai/keys</a></p>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-300">API Key</label>
                        <input type="password" name="openrouter_key" value="{{ $openrouterKey }}" class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40" placeholder="sk-or-v1-..." autocomplete="off" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Model</label>
                        <input type="text" name="openrouter_model" value="{{ $openrouterModel }}" class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40" placeholder="nvidia/nemotron-3.5-lightning:free" />
                        <div class="text-[11px] text-slate-500 mt-1">Free: nvidia/nemotron-3.5-lightning:free, meta-llama/llama-4-scout:free · Paid: openai/gpt-4o-mini, anthropic/claude-3.5-sonnet</div>
                    </div>
                </div>
            </div>

            <!-- OpenAI -->
            <div class="glass rounded-2xl p-5">
                <div class="text-sm font-bold text-white mb-1">OpenAI</div>
                <p class="text-xs text-slate-400 mb-4">Direct OpenAI API access. Get your key at <a href="https://platform.openai.com/api-keys" target="_blank" class="text-brand-400 hover:underline">platform.openai.com</a></p>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-300">API Key</label>
                        <input type="password" name="openai_key" value="{{ $openaiKey }}" class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40" placeholder="sk-..." autocomplete="off" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Model</label>
                        <input type="text" name="openai_model" value="{{ $openaiModel }}" class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40" placeholder="gpt-4o-mini" />
                    </div>
                </div>
            </div>

            <!-- Gemini -->
            <div class="glass rounded-2xl p-5">
                <div class="text-sm font-bold text-white mb-1">Google Gemini</div>
                <p class="text-xs text-slate-400 mb-4">Get your key at <a href="https://aistudio.google.com/apikey" target="_blank" class="text-brand-400 hover:underline">aistudio.google.com</a></p>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-300">API Key</label>
                        <input type="password" name="gemini_key" value="{{ $geminiKey }}" class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40" placeholder="AIza..." autocomplete="off" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Model</label>
                        <input type="text" name="gemini_model" value="{{ $geminiModel }}" class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40" placeholder="gemini-1.5-flash" />
                    </div>
                </div>
            </div>

            <!-- Priority note -->
            <div class="rounded-xl bg-brand-500/10 border border-brand-500/20 p-4 text-xs text-brand-200 leading-relaxed">
                <b>Priority:</b> If multiple keys are set, the app uses <b>OpenRouter → OpenAI → Gemini</b> in that order.
                OpenRouter is recommended because it gives access to free models and a single key for all providers.
            </div>

            <button type="submit" class="btn-primary">Save settings</button>
        </form>
    </main>
    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-600">
        AI Visibility · SaaS owner area · <a href="{{ route('privacy') }}" class="hover:text-slate-400">Privacy</a> · <a href="{{ route('terms') }}" class="hover:text-slate-400">Terms</a>
    </footer>
</body>
</html>
