<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="text-base font-bold text-slate-900">AI Visibility Tracker</div>
          <div class="text-xs text-slate-500 mt-0.5">
            <template v-if="llmMode">LLM mode — real ChatGPT/Gemini answers, checked daily.</template>
            <template v-else>Retrieval-proxy mode (no API key) — checks live web results that feed AI answers. Add an OpenAI/Gemini key for true LLM answers.</template>
          </div>
        </div>
        <button @click="runNow" :disabled="running" class="btn-primary text-xs">
          {{ running ? 'Checking…' : 'Run check now' }}
        </button>
      </div>
    </div>

    <!-- Engine results -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Latest results per engine</div>
      <div v-if="!engines.length" class="text-sm text-slate-500">No data yet — run a check or wait for the daily 6 AM snapshot.</div>
      <div class="grid sm:grid-cols-3 gap-4">
        <div v-for="e in engines" :key="e.engine" class="rounded-xl border border-slate-200 p-4">
          <div class="flex items-center justify-between">
            <span class="text-sm font-bold">{{ (engineMeta[e.engine] || {}).label || e.engine }}</span>
            <span class="text-[11px] text-slate-400">{{ e.date }}</span>
          </div>
          <div class="text-2xl font-extrabold mt-1" :style="{ color: (engineMeta[e.engine] || {}).color }">{{ fmt.pct(e.rate) }}</div>
          <div class="text-xs text-slate-500">{{ e.mentioned }} mentioned · {{ e.cited }} cited · {{ e.total }} queries</div>
          <div class="relative h-1.5 mt-2 rounded-full bg-slate-100">
            <div class="absolute inset-y-0 left-0 rounded-full" :style="{ width: e.rate + '%', background: (engineMeta[e.engine] || {}).color }"></div>
          </div>
          <div v-if="e.samples?.length" class="mt-3 space-y-2">
            <div v-for="(s, i) in e.samples.slice(0, 2)" :key="i" class="text-[11px] text-slate-600 bg-slate-50 rounded-lg p-2 leading-relaxed">
              <span class="font-semibold">“{{ s.query }}”</span> — {{ s.mentioned ? 'mentioned ✓' : 'absent' }}<br>{{ s.snippet }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Queries -->
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <div class="text-sm font-bold text-slate-900">Tracked queries ({{ queries.length }}/{{ queryLimit }})</div>
      </div>
      <form @submit.prevent="add" class="flex gap-2 mb-4">
        <input v-model="newQuery" placeholder='e.g. "best vitamin c serum for Indian skin under 1000"' class="input text-sm" />
        <select v-model="newCat" class="input !w-36 text-sm">
          <option value="brand">Brand</option>
          <option value="product">Product</option>
          <option value="category">Category</option>
          <option value="comparison">Comparison</option>
        </select>
        <button type="submit" class="btn-primary text-xs shrink-0">Add query</button>
      </form>
      <div class="space-y-2">
        <div v-for="q in queries" :key="q.id" class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-2.5">
          <div class="min-w-0">
            <div class="text-sm font-semibold text-slate-800 truncate">{{ q.query }}</div>
            <div class="text-[11px] text-slate-400 capitalize">{{ q.category }} · {{ q.active ? 'active' : 'paused' }}</div>
          </div>
          <button @click="remove(q)" class="text-xs font-semibold text-red-500 hover:text-red-700 shrink-0 ml-3">Remove</button>
        </div>
        <div v-if="!queries.length" class="text-sm text-slate-500 py-2">Add queries Indian shoppers actually ask AI — product, category and comparison questions.</div>
      </div>
    </div>

    <!-- Competitors -->
    <div class="stat-card">
      <div class="flex items-center justify-between mb-3">
        <div class="text-sm font-bold text-slate-900">Competitor watch — who does AI mention?</div>
      </div>
      <form @submit.prevent="addCompetitor" class="flex gap-2 mb-4">
        <input v-model="compName" placeholder="Competitor name, e.g. Minimalist" class="input text-sm" />
        <input v-model="compDomain" placeholder="domain.in (no https)" class="input text-sm" />
        <button type="submit" class="btn-primary text-xs shrink-0">Track competitor</button>
      </form>
      <div v-if="!competitors.length" class="text-sm text-slate-500">Add competitors to compare mention rates for the same queries.</div>
      <div class="space-y-3">
        <div v-for="c in competitors" :key="c.id" class="rounded-xl border border-slate-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm font-bold">{{ c.name }}</div>
              <div class="text-[11px] text-slate-500">{{ c.domain }}</div>
            </div>
            <button @click="removeCompetitor(c)" class="text-xs font-semibold text-red-500 hover:text-red-700">Remove</button>
          </div>
          <div class="mt-3 space-y-2">
            <div class="flex items-center gap-3">
              <span class="text-[11px] font-semibold text-slate-500 w-16">You</span>
              <div class="flex-1 h-2 rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-brand-600" :style="{ width: c.my_rate + '%' }"></div>
              </div>
              <span class="text-xs font-bold w-10 text-right">{{ c.my_rate }}%</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-[11px] font-semibold text-slate-500 w-16">{{ c.name }}</span>
              <div class="flex-1 h-2 rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-slate-400" :style="{ width: c.rate + '%' }"></div>
              </div>
              <span class="text-xs font-bold w-10 text-right">{{ c.rate }}%</span>
            </div>
            <div class="text-[11px] text-slate-500">{{ c.mentioned }}/{{ c.total }} queries mention {{ c.name }} (latest snapshot)</div>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
      <b>Query tips:</b> include Indian context (“under ₹1000”, “for Indian skin”, “in India”), price ranges, and comparisons.
      AI engines surface brands that match the exact intent + have extractable, structured answers.
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api, engineMeta, fmt } from '../api';

const queries = ref([]);
const engines = ref([]);
const llmMode = ref(false);
const running = ref(false);
const queryLimit = ref(25);
const newQuery = ref('');
const newCat = ref('brand');
const competitors = ref([]);
const compName = ref('');
const compDomain = ref('');

async function load() {
    const d = await api.get('/api/tracker');
    queries.value = d.queries || [];
    engines.value = d.engines || [];
    llmMode.value = d.llm_mode;
    queryLimit.value = d.query_limit ?? queryLimit.value;
    try {
        const c = await api.get('/api/tracker/competitors');
        competitors.value = c.competitors || [];
    } catch (e) { /* optional */ }
}

async function add() {
    if (!newQuery.value.trim()) return;
    await api.post('/api/tracker/query', { query: newQuery.value, category: newCat.value });
    newQuery.value = '';
    await load();
}

async function remove(q) {
    await api.del('/api/tracker/query/' + q.id);
    await load();
}

async function addCompetitor() {
    if (!compName.value.trim() || !compDomain.value.trim()) return;
    await api.post('/api/tracker/competitors', { name: compName.value, domain: compDomain.value });
    compName.value = '';
    compDomain.value = '';
    await load();
}

async function removeCompetitor(c) {
    await api.del('/api/tracker/competitors/' + c.id);
    await load();
}

async function runNow() {
    running.value = true;
    try {
        await api.post('/api/tracker/run');
        await load();
    } catch (e) {
        alert(e.message);
    } finally {
        running.value = false;
    }
}

onMounted(async () => {
    try { await load(); } catch (e) { /* session */ }
});
</script>
