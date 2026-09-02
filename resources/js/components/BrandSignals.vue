<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="text-base font-bold text-slate-900">Brand Signals</div>
          <div class="text-xs text-slate-500 mt-0.5">
            The third-party trust layer AI engines weigh when deciding whether to cite you — ratings,
            reviews, review-platform presence, off-site mentions and social profiles.
          </div>
        </div>
        <button @click="runNow" :disabled="running" class="btn-primary text-xs">
          {{ running ? 'Checking…' : 'Run signals check' }}
        </button>
      </div>
    </div>

    <div v-if="!run && !running" class="stat-card text-sm text-slate-500">
      No Brand Signals check yet — run one to see how much third-party trust your brand has.
    </div>

    <template v-else>
      <div class="stat-card">
        <div class="flex items-center gap-4 flex-wrap">
          <div class="text-center shrink-0">
            <div class="text-4xl font-extrabold text-brand-600">{{ run?.score ?? '…' }}<span class="text-base text-slate-400">/100</span></div>
            <div class="text-xs text-slate-500">Grade {{ run?.summary?.grade }}</div>
          </div>
          <div class="text-xs text-slate-500 leading-relaxed">
            Signals that AIs can verify about you outside your own site.<br>
            <span v-if="run?.summary?.checked_at">Checked {{ new Date(run.summary.checked_at).toLocaleString('en-IN') }}</span>
          </div>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div v-for="c in run?.checks" :key="c.key" class="stat-card">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-sm font-bold text-slate-900">{{ c.label }}</div>
              <div class="text-[11px] text-slate-600 mt-1.5 leading-relaxed">{{ c.detail }}</div>
              <div v-if="!c.found" class="text-[11px] text-amber-700 bg-amber-50 rounded-lg p-2 mt-2 leading-relaxed">{{ c.fix }}</div>
            </div>
            <div class="text-right shrink-0">
              <span :class="c.found ? 'badge-green badge' : 'badge-slate badge'">{{ c.found ? 'Found' : 'Missing' }}</span>
              <div class="text-[10px] text-slate-400 mt-1">{{ c.score }}/{{ c.max }} pts</div>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
        <b>Why this matters:</b> third-party ratings/reviews on trusted platforms are the strongest documented
        correlate of AI citations. This panel checks the honest signals — if a probe can’t reach the web it says so,
        instead of guessing.
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';

const run = ref(null);
const running = ref(false);

async function load() {
    const d = await api.get('/api/brand-signals');
    run.value = d.run || null;
}

async function runNow() {
    running.value = true;
    try {
        const d = await api.post('/api/brand-signals/run');
        run.value = d.run || null;
    } catch (e) {
        alert(e.message);
    } finally {
        running.value = false;
    }
}

onMounted(load);
</script>
