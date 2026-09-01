<template>
  <div class="space-y-5">
    <!-- Score hero -->
    <div class="grid md:grid-cols-3 gap-5">
      <div class="stat-card flex items-center gap-5">
        <svg width="120" height="120" viewBox="0 0 120 120" class="shrink-0">
          <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="10" />
          <circle cx="60" cy="60" r="52" fill="none" stroke="#0a84ff" stroke-width="10"
                  :stroke-dasharray="circ" :stroke-dashoffset="offset" stroke-linecap="round"
                  transform="rotate(-90 60 60)" />
          <text x="60" y="66" text-anchor="middle" class="text-2xl font-extrabold" fill="#0f172a">
            {{ data.score !== null ? data.score : '—' }}
          </text>
          <text x="60" y="84" text-anchor="middle" font-size="10" fill="#64748b">AI READINESS</text>
        </svg>
        <div>
          <div class="text-sm font-bold text-slate-900">AI Readiness Score</div>
          <div class="text-xs text-slate-500 mt-1">
            <template v-if="data.score !== null">Grade <b>{{ data.grade }}</b> · {{ data.open_issues }} open fixes · last audit {{ fmt.date(data.last_audit_at) }}</template>
            <template v-else>No audit yet — run your first AI Readiness audit.</template>
          </div>
          <button @click="$emit('goto', 'audit')" class="btn-primary mt-3 !py-2 text-xs">Run audit</button>
        </div>
      </div>

      <div class="stat-card">
        <div class="text-sm font-bold text-slate-900">Mentions today</div>
        <div class="mt-2 flex items-end gap-2">
          <span class="text-4xl font-extrabold text-slate-900">{{ data.mentions_today?.mentioned ?? 0 }}</span>
          <span class="text-sm text-slate-500 pb-1">of {{ data.mentions_today?.total ?? 0 }} queries</span>
        </div>
        <div class="text-xs text-slate-500 mt-1">Brand appearances in AI answers across tracked engines</div>
      </div>

      <div class="stat-card">
        <div class="text-sm font-bold text-slate-900">AI growth trend</div>
        <div class="mt-3 h-20 relative">
          <svg :viewBox="`0 0 ${vw} 80`" preserveAspectRatio="none" class="w-full h-full">
            <polyline :points="polyPoints" fill="none" stroke="#0a84ff" stroke-width="2.5" />
            <circle v-if="points.length" :cx="points[points.length-1][0]" :cy="points[points.length-1][1]" r="3.5" fill="#0a84ff" />
          </svg>
        </div>
        <div class="flex justify-between text-[11px] text-slate-400 mt-1">
          <span>{{ data.trend?.[0]?.date }}</span>
          <span>{{ data.trend?.[data.trend.length-1]?.date }}</span>
        </div>
      </div>
    </div>

    <!-- Category scores -->
    <div v-if="data.categories" class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Score by category</div>
      <div class="grid sm:grid-cols-5 gap-4">
        <div v-for="(v, k) in data.categories" :key="k" class="text-center">
          <div class="text-xs font-semibold text-slate-500 capitalize mb-1">{{ labels[k] || k }}</div>
          <div class="relative h-2 rounded-full bg-slate-100">
            <div class="absolute inset-y-0 left-0 rounded-full" :class="barColor(v)" :style="{ width: v + '%' }"></div>
          </div>
          <div class="text-sm font-bold mt-1">{{ v }}</div>
        </div>
      </div>
    </div>

    <!-- Engines -->
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <div class="text-sm font-bold text-slate-900">AI engine visibility</div>
        <button @click="$emit('goto', 'tracker')" class="text-xs font-semibold text-brand-600 hover:underline">Track queries →</button>
      </div>
      <div v-if="!data.engines?.length" class="text-sm text-slate-500">Run the tracker to see per-engine mention rates.</div>
      <div class="grid sm:grid-cols-3 gap-4">
        <div v-for="e in data.engines" :key="e.engine" class="rounded-xl border border-slate-200 p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm font-bold">{{ (engineMeta[e.engine] || {}).label || e.engine }}</span>
            <span class="text-xs text-slate-400">{{ e.mentioned }}/{{ e.total }}</span>
          </div>
          <div class="text-2xl font-extrabold mt-1" :style="{ color: (engineMeta[e.engine] || {}).color }">{{ fmt.pct(e.rate) }}</div>
          <div class="relative h-1.5 mt-2 rounded-full bg-slate-100">
            <div class="absolute inset-y-0 left-0 rounded-full" :style="{ width: e.rate + '%', background: (engineMeta[e.engine] || {}).color }"></div>
          </div>
          <div v-if="e.samples?.length" class="mt-3 space-y-2">
            <div v-for="(s, i) in e.samples.slice(0, 2)" :key="i" class="text-[11px] text-slate-600 bg-slate-50 rounded-lg p-2 leading-relaxed">
              <span class="font-semibold text-slate-800">“{{ s.query }}”</span><br>{{ s.snippet }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Honesty note -->
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 leading-relaxed">
      <b>How this works (honest mode):</b> AI visibility grows over 2–6 months. We track real mention rates per query and engine, and fix the
      technical signals that retrieval-based AI answers rely on (robots.txt, schema, crawlable content, brand signals).
      Nobody can guarantee rankings in ChatGPT — anyone who does is selling snake oil.
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { engineMeta, fmt } from '../api';

const props = defineProps({ data: Object });
defineEmits(['goto', 'refresh']);

const labels = { crawlability: 'Crawlability', schema: 'Schema', content: 'Content', brand: 'Brand', speed: 'Speed' };
const vw = 300;

const circ = 2 * Math.PI * 52;
const offset = computed(() => {
    const score = props.data.score ?? 0;
    return circ * (1 - Math.min(100, score) / 100);
});

const points = computed(() => {
    const trend = props.data.trend || [];
    if (!trend.length) return [];
    const max = Math.max(5, ...trend.map((t) => t.rate));
    const w = vw;
    const h = 80;
    return trend.map((t, i) => [
        (i / Math.max(1, trend.length - 1)) * w,
        h - 6 - (t.rate / max) * (h - 16),
    ]);
});

const polyPoints = computed(() => points.value.map((p) => p.join(',')).join(' '));

function barColor(v) {
    if (v >= 80) return 'bg-emerald-500';
    if (v >= 60) return 'bg-amber-500';
    return 'bg-red-500';
}
</script>
