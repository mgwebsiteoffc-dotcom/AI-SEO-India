<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-base font-bold text-slate-900">Brand Signals</div>
          <p class="text-xs text-slate-500 mt-0.5">
            Build trust signals that help AI engines recognize and recommend your brand.
          </p>
        </div>
        <div class="flex gap-2">
          <button @click="analyze" :disabled="loading" class="btn-primary text-xs">
            {{ loading ? 'Analyzing…' : 'Analyze brand signals' }}
          </button>
          <button v-if="result" @click="refresh" :disabled="loading" class="btn-secondary text-xs">
            {{ loading ? 'Refreshing…' : 'Refresh' }}
          </button>
        </div>
      </div>
    </div>

    <template v-if="result">
      <div class="stat-card">
        <div class="flex items-center gap-6">
          <div>
            <div class="text-5xl font-extrabold" :class="scoreColor">{{ result.score }}<span class="text-xl text-slate-400">/100</span></div>
            <div class="text-xs text-slate-500 mt-1">Brand signal strength</div>
          </div>
          <div class="flex-1">
            <div class="text-sm font-bold text-slate-900">{{ result.brand }}</div>
            <div class="text-xs text-slate-500">{{ result.domain }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ result.found }}/{{ result.total }} signals found</div>
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="text-sm font-bold text-slate-900 mb-3">Signal breakdown</div>
        <div class="space-y-3">
          <div v-for="signal in result.signals" :key="signal.name" class="rounded-xl border p-4"
               :class="signal.status === 'found' ? 'border-emerald-200 bg-emerald-50/50' : signal.status === 'partial' ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200'">
            <div class="flex items-start gap-3">
              <span class="mt-0.5 shrink-0 text-lg">
                {{ signal.status === 'found' ? '✅' : signal.status === 'partial' ? '⚠️' : '❌' }}
              </span>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-bold text-slate-900">{{ signal.name }}</div>
                <div class="text-xs text-slate-600 mt-1">{{ signal.description }}</div>
                <div v-if="signal.action" class="text-xs text-brand-700 mt-2 bg-brand-50 rounded-lg p-2.5">
                  <b>Action:</b> {{ signal.action }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1">Impact: {{ signal.impact }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="stat-card text-center py-8 text-sm text-slate-500">
      Click "Analyze brand signals" to check your brand presence across the web.
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';

const loading = ref(false);
const result = ref(null);

const scoreColor = computed(() => {
    const s = result.value?.score ?? 0;
    return s >= 80 ? 'text-emerald-600' : s >= 60 ? 'text-amber-600' : 'text-red-600';
});

// Auto-load cached results on mount
onMounted(async () => {
    try {
        const cached = await api.get('/api/brand-signals');
        if (cached && cached.ok !== false) {
            result.value = cached;
        }
    } catch (e) {
        // No cached data — that's fine
    }
});

async function analyze() {
    loading.value = true;
    result.value = null;
    try {
        result.value = await api.get('/api/brand-signals');
    } catch (e) {
        alert(e.message);
    } finally {
        loading.value = false;
    }
}

async function refresh() {
    loading.value = true;
    try {
        result.value = await api.get('/api/brand-signals?refresh=1');
    } catch (e) {
        alert(e.message);
    } finally {
        loading.value = false;
    }
}
</script>
