@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 mt-4 text-xs" role="navigation" aria-label="Pagination">
        <span class="text-slate-500">
            Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        </span>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="opacity-40 cursor-not-allowed border border-white/10 rounded-lg px-2.5 py-1.5">← Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="border border-white/10 rounded-lg px-2.5 py-1.5 hover:border-brand-500/50 text-slate-300">← Prev</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-slate-600">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="px-2.5 py-1.5 rounded-lg bg-brand-500/25 text-brand-300 font-bold">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-2.5 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="border border-white/10 rounded-lg px-2.5 py-1.5 hover:border-brand-500/50 text-slate-300">Next →</a>
            @else
                <span class="opacity-40 cursor-not-allowed border border-white/10 rounded-lg px-2.5 py-1.5">Next →</span>
            @endif
        </div>
    </nav>
@endif
