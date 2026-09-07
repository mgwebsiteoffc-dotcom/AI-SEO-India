<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-base font-bold text-slate-900">Analytics & Reports</div>
          <p class="text-xs text-slate-500 mt-0.5">
            Historical data, trends, and comprehensive reports for your AI visibility.
          </p>
        </div>
        <div class="flex gap-2">
          <select v-model="period" class="input !w-32 text-xs">
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
          </select>
          <button @click="load" :disabled="loading" class="btn-secondary text-xs">
            {{ loading ? 'Loading…' : 'Refresh' }}
          </button>
          <button @click="exportReport" class="btn-primary text-xs">
            Export PDF
          </button>
        </div>
      </div>
    </div>

    <!-- Score cards -->
    <div class="grid sm:grid-cols-3 gap-4">
      <div class="stat-card text-center">
        <div class="text-xs text-slate-500">AI Visibility Score</div>
        <div class="text-3xl font-extrabold mt-1" :class="scoreColor(data.ai_visibility?.current)">
          {{ data.ai_visibility?.current ?? '—' }}
        </div>
        <div class="text-xs mt-1" :class="changeColor(data.ai_visibility?.change)">
          {{ changeText(data.ai_visibility?.change) }}
        </div>
      </div>
      <div class="stat-card text-center">
        <div class="text-xs text-slate-500">Brand Signals</div>
        <div class="text-3xl font-extrabold mt-1" :class="scoreColor(data.brand_signals?.current)">
          {{ data.brand_signals?.current ?? '—' }}
        </div>
        <div class="text-xs mt-1" :class="changeColor(data.brand_signals?.change)">
          {{ changeText(data.brand_signals?.change) }}
        </div>
      </div>
      <div class="stat-card text-center">
        <div class="text-xs text-slate-500">Speed Score</div>
        <div class="text-3xl font-extrabold mt-1" :class="scoreColor(data.speed_analysis?.current)">
          {{ data.speed_analysis?.current ?? '—' }}
        </div>
        <div class="text-xs mt-1" :class="changeColor(data.speed_analysis?.change)">
          {{ changeText(data.speed_analysis?.change) }}
        </div>
      </div>
    </div>

    <!-- AI Traffic -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">AI Traffic Overview</div>
      <div class="grid sm:grid-cols-4 gap-3">
        <div class="rounded-xl border border-slate-200 p-3 text-center">
          <div class="text-xs text-slate-500">Total Visits</div>
          <div class="text-xl font-extrabold mt-1">{{ data.ai_traffic?.total_visits ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 p-3 text-center">
          <div class="text-xs text-slate-500">Revenue</div>
          <div class="text-xl font-extrabold mt-1">₹{{ formatNumber(data.ai_traffic?.total_revenue ?? 0) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 p-3 text-center">
          <div class="text-xs text-slate-500">Conversions</div>
          <div class="text-xl font-extrabold mt-1">{{ data.ai_traffic?.total_conversions ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 p-3 text-center">
          <div class="text-xs text-slate-500">Conv. Rate</div>
          <div class="text-xl font-extrabold mt-1">{{ conversionRate }}%</div>
        </div>
      </div>
    </div>

    <!-- Traffic by source -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Traffic by AI Source</div>
      <div v-if="!Object.keys(data.ai_traffic?.by_source || {}).length" class="text-sm text-slate-500 py-3">
        No AI traffic recorded yet. Traffic will appear once customers visit from AI platforms.
      </div>
      <div v-else class="space-y-3">
        <div v-for="(stats, source) in data.ai_traffic?.by_source" :key="source" class="rounded-xl border border-slate-200 p-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm font-bold capitalize">{{ source }}</div>
              <div class="text-xs text-slate-500">{{ stats.visits }} visits · {{ stats.conversions }} conversions</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-bold">₹{{ formatNumber(stats.revenue) }}</div>
              <div class="text-xs text-slate-500">revenue</div>
            </div>
          </div>
          <div class="w-full h-2 mt-2 rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-brand-500" :style="{ width: trafficBarWidth(stats.visits) + '%' }"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Performance -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Content Performance</div>
      <div class="grid sm:grid-cols-4 gap-3">
        <div class="rounded-xl border border-slate-200 p-3 text-center">
          <div class="text-xs text-slate-500">Total Posts</div>
          <div class="text-xl font-extrabold mt-1">{{ data.content_performance?.total_posts ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 p-3 text-center">
          <div class="text-xs text-slate-500">Published</div>
          <div class="text-xl font-extrabold mt-1 text-emerald-600">{{ data.content_performance?.published ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 p-3 text-center">
          <div class="text-xs text-slate-500">Drafts</div>
          <div class="text-xl font-extrabold mt-1 text-amber-600">{{ data.content_performance?.draft ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 p-3 text-center">
          <div class="text-xs text-slate-500">Total Words</div>
          <div class="text-xl font-extrabold mt-1">{{ formatNumber(data.content_performance?.total_words ?? 0) }}</div>
        </div>
      </div>
    </div>

    <!-- Trend charts -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Score Trends</div>
      <div v-if="!data.ai_visibility?.trend?.length" class="text-sm text-slate-500 py-3">
        No historical data yet. Run analyses to start tracking trends.
      </div>
      <div v-else class="h-48 flex items-end gap-1">
        <div v-for="point in data.ai_visibility?.trend" :key="point.date" class="flex-1 flex flex-col items-center min-w-0">
          <div class="text-[10px] text-slate-500">{{ point.score }}</div>
          <div class="w-full rounded-t bg-brand-500" :style="{ height: (point.score / 100 * 100) + '%' }"></div>
          <div class="text-[9px] text-slate-400 mt-1 truncate w-full text-center">{{ point.date }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { api } from '../api';

const loading = ref(false);
const period = ref(30);
const data = ref({});

const conversionRate = computed(() => {
    const visits = data.value.ai_traffic?.total_visits ?? 0;
    const conversions = data.value.ai_traffic?.total_conversions ?? 0;
    return visits > 0 ? ((conversions / visits) * 100).toFixed(1) : '0.0';
});

function formatNumber(n) {
    return Number(n).toLocaleString('en-IN');
}

function scoreColor(score) {
    if (!score) return 'text-slate-400';
    return score >= 80 ? 'text-emerald-600' : score >= 60 ? 'text-amber-600' : 'text-red-600';
}

function changeColor(change) {
    if (!change) return 'text-slate-500';
    return change > 0 ? 'text-emerald-600' : change < 0 ? 'text-red-600' : 'text-slate-500';
}

function changeText(change) {
    if (!change) return 'No change';
    return change > 0 ? `↑ +${change}` : change < 0 ? `↓ ${change}` : 'No change';
}

function trafficBarWidth(visits) {
    const max = Math.max(1, ...Object.values(data.value.ai_traffic?.by_source || {}).map(s => s.visits));
    return (visits / max) * 100;
}

async function load() {
    loading.value = true;
    try {
        data.value = await api.get(`/api/analytics?days=${period.value}`);
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

function exportReport() {
    // Open report in new tab for printing/PDF
    window.open(`/api/analytics/report?days=${period.value}&format=html`, '_blank');
}

onMounted(load);
watch(period, load);
</script>
