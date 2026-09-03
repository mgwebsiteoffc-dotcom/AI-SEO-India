<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="text-base font-bold text-slate-900">AI Readiness Audit</div>
          <div class="text-xs text-slate-500 mt-0.5">
            30+ evidence-based checks: crawlability · schema · content · brand · speed
          </div>
        </div>
        <div class="flex gap-2 items-center">
          <input v-model="domainOverride" placeholder="custom domain (optional)" class="input !w-56 text-xs" />
          <button @click="run" :disabled="running" class="btn-primary text-xs">
            {{ running ? 'Auditing…' : 'Run audit' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="running" class="stat-card text-center py-10">
      <div class="animate-spin w-8 h-8 border-4 border-brand-600 border-t-transparent rounded-full mx-auto"></div>
      <div class="text-sm text-slate-600 mt-3">Crawling your storefront, checking robots.txt, sitemap, schema, content…</div>
      <div class="text-xs text-slate-400 mt-1">Takes ~15–30 seconds</div>
    </div>

    <template v-else>
      <div v-if="result" class="stat-card">
        <div class="flex items-center gap-6 flex-wrap">
          <div>
            <div class="text-5xl font-extrabold" :class="scoreColor">{{ result.score }}<span class="text-xl text-slate-400">/100</span></div>
            <div class="text-xs text-slate-500 mt-1">Grade {{ result.summary?.grade }} · {{ fmt.date(result.summary?.checked_at) }}</div>
          </div>
          <div class="flex-1 min-w-[240px] grid sm:grid-cols-5 gap-3">
            <div v-for="(v, k) in result.summary?.categories" :key="k" class="text-center">
              <div class="text-[11px] font-semibold text-slate-500 capitalize">{{ k }}</div>
              <div class="text-lg font-bold" :class="v >= 80 ? 'text-emerald-600' : v >= 60 ? 'text-amber-600' : 'text-red-600'">{{ v }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="issues.length" class="stat-card">
        <div class="text-sm font-bold text-slate-900 mb-3">Action plan — {{ openCount }} fixes to make</div>
        <div class="space-y-3">
          <div v-for="issue in issues" :key="issue.id"
               class="rounded-xl border p-4 transition-opacity"
               :class="issue.is_fixed ? 'border-emerald-200 bg-emerald-50/50 opacity-70' : severityBorder[issue.severity]">
            <div class="flex items-start gap-3">
              <label class="flex items-center gap-2 mt-0.5 shrink-0 cursor-pointer">
                <input type="checkbox" :checked="issue.is_fixed" @change="toggleFixed(issue)"
                       class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
              </label>
              <span :class="severityBadge[issue.severity]" class="mt-0.5 shrink-0">{{ issue.severity }}</span>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-bold flex items-center gap-2"
                     :class="issue.is_fixed ? 'text-emerald-700 line-through' : 'text-slate-900'">
                  {{ issue.title }}
                  <span v-if="issue.is_fixed" class="badge-green">Done</span>
                </div>
                <div class="text-xs mt-1 leading-relaxed"
                     :class="issue.is_fixed ? 'text-emerald-600' : 'text-slate-600'">{{ issue.detail }}</div>
                <div v-if="!issue.is_fixed" class="text-xs text-brand-700 mt-2 bg-brand-50 rounded-lg p-2.5 leading-relaxed">
                  <b>Fix:</b> {{ issue.recommendation }}
                </div>
                <div v-if="fixHints[issue.code] && !issue.is_fixed" class="mt-2">
                  <button @click="applyFix(issue)" class="btn-primary !py-1.5 text-xs" :disabled="fixing === issue.id">
                    {{ fixing === issue.id ? 'Applying…' : fixHints[issue.code] }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="result && !issues.length" class="stat-card text-center py-8">
        <div class="text-2xl">🎉</div>
        <div class="text-sm font-bold mt-2">No issues found — keep shipping content and reviews!</div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { api, fmt, severityBadge } from '../api';

const props = defineProps({ initial: Object });
const emit = defineEmits(['audited']);

const running = ref(false);
const fixing = ref(null);
const result = ref(null);
const issues = ref([]);
const domainOverride = ref(props.initial.store?.domain && !props.initial.store?.domain.includes('myshopify') ? props.initial.store.domain : '');

const severityBorder = { critical: 'border-red-200', warning: 'border-amber-200', info: 'border-slate-200' };
const scoreColor = computed(() => {
    const s = result.value?.score ?? 0;
    return s >= 80 ? 'text-emerald-600' : s >= 60 ? 'text-amber-600' : 'text-red-600';
});
const openCount = computed(() => issues.value.filter((i) => !i.is_fixed).length);

const fixHints = {
    schema_product_missing: 'One-click: enable Product + FAQ schema',
    schema_home_missing: 'One-click: install Organization schema',
    robots_blocking_ai: 'One-click: allow AI crawlers',
    robots_no_sitemap: 'One-click: add sitemap line',
    thin_product_content: 'Open Smart Blogger to draft richer copy',
};

async function run() {
    running.value = true;
    result.value = null;
    issues.value = [];
    try {
        const res = await api.post('/api/audit/run', {
            overrides: { domain: domainOverride.value || undefined },
        });
        result.value = res;
        issues.value = res.issues || [];
        emit('audited');
    } catch (e) {
        alert(e.message);
    } finally {
        running.value = false;
    }
}

async function toggleFixed(issue) {
    try {
        const res = await api.post(`/api/audit/issue/${issue.id}/toggle`);
        issue.is_fixed = res.is_fixed;
    } catch (e) {
        alert(e.message);
    }
}

async function applyFix(issue) {
    fixing.value = issue.id;
    try {
        if (issue.code === 'schema_product_missing' || issue.code === 'schema_home_missing') {
            await api.post('/api/schema/install');
            emit('goto', 'schema');
        } else if (issue.code === 'robots_blocking_ai' || issue.code === 'robots_no_sitemap') {
            await api.post('/api/llms/generate');
            emit('goto', 'llms');
        } else {
            emit('goto', 'settings');
        }
    } catch (e) {
        alert(e.message);
    } finally {
        fixing.value = null;
    }
}
</script>
