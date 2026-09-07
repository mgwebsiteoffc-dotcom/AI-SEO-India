<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-base font-bold text-slate-900">Page Speed Analysis</div>
          <p class="text-xs text-slate-500 mt-0.5">
            Check your store's page speed and Core Web Vitals using Google PageSpeed Insights.
          </p>
        </div>
        <div class="flex gap-2">
          <button @click="analyze" :disabled="loading" class="btn-primary text-xs">
            {{ loading ? 'Analyzing…' : 'Run speed analysis' }}
          </button>
          <button v-if="result" @click="refresh" :disabled="loading" class="btn-secondary text-xs">
            {{ loading ? 'Refreshing…' : 'Refresh' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="stat-card text-center py-10">
      <div class="animate-spin w-8 h-8 border-4 border-brand-600 border-t-transparent rounded-full mx-auto"></div>
      <div class="text-sm text-slate-600 mt-3">Running Google PageSpeed Insights…</div>
      <div class="text-xs text-slate-400 mt-1">This may take 15–30 seconds</div>
    </div>

    <template v-else-if="result">
      <div v-if="!result.ok" class="stat-card text-center py-8">
        <div class="text-sm text-red-600 mb-2">{{ result.error }}</div>
        <div v-if="result.error?.includes('429')" class="text-xs text-slate-500">
          Google PageSpeed API rate limit reached. Results are cached — try again in a few minutes or click "Refresh" to force a new analysis.
        </div>
      </div>

      <template v-else>
        <!-- Scores -->
        <div class="grid sm:grid-cols-4 gap-4">
          <div v-for="(score, key) in result.scores" :key="key" class="stat-card text-center">
            <div class="text-xs text-slate-500 capitalize">{{ key.replace('_', ' ') }}</div>
            <div class="text-3xl font-extrabold mt-1" :class="scoreColor(score)">{{ score }}</div>
            <div class="w-full h-2 mt-2 rounded-full bg-slate-100">
              <div class="h-full rounded-full" :class="scoreBg(score)" :style="{ width: score + '%' }"></div>
            </div>
          </div>
        </div>

        <!-- Core Web Vitals -->
        <div class="stat-card">
          <div class="text-sm font-bold text-slate-900 mb-3">Core Web Vitals</div>
          <div class="space-y-3">
            <div v-for="metric in result.metrics" :key="metric.id" class="flex items-center gap-4">
              <div class="w-40 text-xs font-semibold text-slate-700">{{ metric.label }}</div>
              <div class="flex-1 h-2 rounded-full bg-slate-100">
                <div class="h-full rounded-full" :class="metricColor(metric.score)" :style="{ width: (metric.score * 100) + '%' }"></div>
              </div>
              <div class="w-24 text-xs font-bold text-right" :class="metricTextColor(metric.score)">{{ metric.value }}</div>
            </div>
          </div>
        </div>

        <!-- Opportunities -->
        <div v-if="result.opportunities?.length" class="stat-card">
          <div class="text-sm font-bold text-slate-900 mb-3">Top opportunities to improve</div>
          <div class="space-y-3">
            <div v-for="opp in result.opportunities" :key="opp.id" class="rounded-xl border border-amber-200 bg-amber-50/50 p-4">
              <div class="text-sm font-bold text-slate-900">{{ opp.title }}</div>
              <div class="text-xs text-slate-600 mt-1">{{ opp.description }}</div>
              <div v-if="opp.savings" class="text-xs text-amber-700 mt-1 font-semibold">
                Potential savings: {{ opp.savings }}ms
              </div>
            </div>
          </div>
        </div>
      </template>
    </template>

    <div v-else class="stat-card text-center py-8 text-sm text-slate-500">
      Click "Run speed analysis" to check your store's performance.
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { api } from '../api';

const loading = ref(false);
const result = ref(null);

function scoreColor(score) {
    return score >= 90 ? 'text-emerald-600' : score >= 50 ? 'text-amber-600' : 'text-red-600';
}

function scoreBg(score) {
    return score >= 90 ? 'bg-emerald-500' : score >= 50 ? 'bg-amber-500' : 'bg-red-500';
}

function metricColor(score) {
    if (score === null) return 'bg-slate-300';
    return score >= 0.9 ? 'bg-emerald-500' : score >= 0.5 ? 'bg-amber-500' : 'bg-red-500';
}

function metricTextColor(score) {
    if (score === null) return 'text-slate-500';
    return score >= 0.9 ? 'text-emerald-600' : score >= 0.5 ? 'text-amber-600' : 'text-red-600';
}

async function analyze() {
    loading.value = true;
    result.value = null;
    try {
        result.value = await api.get('/api/speed-analysis');
    } catch (e) {
        alert(e.message);
    } finally {
        loading.value = false;
    }
}

async function refresh() {
    loading.value = true;
    try {
        result.value = await api.get('/api/speed-analysis?refresh=1');
    } catch (e) {
        alert(e.message);
    } finally {
        loading.value = false;
    }
}
</script>
