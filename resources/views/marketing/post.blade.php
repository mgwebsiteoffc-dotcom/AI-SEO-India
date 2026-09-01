<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }} — AI Visibility</title>
    <meta name="description" content="{{ $post->meta_description }}">
    <script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'BlogPosting','headline'=>$post->title,'datePublished'=>$post->published_at?->toIso8601String(),'author'=>['@type'=>'Organization','name'=>$post->author]], JSON_UNESCAPED_SLASHES) !!}</script>
    @vite(['resources/css/app.css'])
    <style>.hero-bg { background: linear-gradient(160deg, #0f172a 0%, #111c34 60%, #0f172a 100%); } .prose h2 { @apply text-xl font-extrabold mt-8 mb-3; } .prose h3 { @apply text-lg font-bold mt-6 mb-2; } .prose p { @apply text-slate-300 leading-relaxed my-3; } .prose ul { @apply list-disc pl-6 text-slate-300 space-y-1.5 my-3; } .prose a { @apply text-brand-500 underline; } .prose strong { @apply text-white; }</style>
</head>
<body class="bg-slate-950 text-slate-100">
    <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-500 flex items-center justify-center font-extrabold text-white">AI</div>
                <div class="font-bold">AI Visibility</div>
            </a>
            <a href="{{ route('blog') }}" class="text-xs font-semibold text-slate-400 hover:text-white">← Blog</a>
        </div>
    </header>

    <article class="max-w-3xl mx-auto px-4 py-12">
        <div class="text-[11px] text-slate-500">{{ $post->published_at?->format('d M Y') }} · {{ $post->author }}</div>
        <h1 class="text-3xl md:text-4xl font-extrabold mt-3 leading-tight">{{ $post->title }}</h1>
        <div class="prose mt-8">{!! $post->body !!}</div>

        <div class="mt-12 rounded-2xl bg-slate-900 border border-brand-500/40 p-6 text-center">
            <div class="font-bold">Is your store ready for AI shopping?</div>
            <p class="text-sm text-slate-400 mt-2">Get your free AI Readiness Score — 30+ checks, one scorecard.</p>
            <a href="{{ route('scorecard') }}" class="btn-primary !bg-brand-500 mt-4">Get my free score</a>
        </div>
    </article>

    <footer class="border-t border-white/10 py-8 text-center text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-slate-300">AI Visibility</a> · <a href="{{ route('pricing') }}" class="hover:text-slate-300">Pricing</a>
    </footer>
</body>
</html>
