<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="text-base font-bold text-slate-900">AI Traffic → Orders</div>
      <div class="text-xs text-slate-500 mt-0.5">
        Orders attributed to AI platforms from your <code class="bg-slate-100 px-1 rounded">orders/paid</code> webhook — no extra setup.
      </div>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
      <div class="stat-card">
        <div class="text-sm font-bold text-slate-900">AI-attributed orders</div>
        <div class="text-4xl font-extrabold mt-2 text-brand-600">{{ report.total_orders ?? 0 }}</div>
        <div class="text-xs text-slate-500 mt-1">all time (last 30 days shown)</div>
      </div>
      <div class="stat-card">
        <div class="text-sm font-bold text-slate-900">Revenue from AI</div>
        <div class="text-4xl font-extrabold mt-2">{{ fmt.inr(report.total_revenue) }}</div>
        <div class="text-xs text-slate-500 mt-1">AOV {{ fmt.inr(report.avg_order_value) }}</div>
      </div>
      <div class="stat-card">
        <div class="text-sm font-bold text-slate-900">Best channel</div>
        <template v-if="bestChannel">
          <div class="text-2xl font-extrabold mt-2 capitalize">{{ bestChannel.label }}</div>
          <div class="text-xs text-slate-500 mt-1">{{ bestChannel.orders }} orders · {{ fmt.inr(bestChannel.revenue) }}</div>
        </template>
        <div v-else class="text-sm text-slate-500 mt-3">No AI orders yet — they appear automatically once customers arrive via ChatGPT/Gemini links.</div>
      </div>
    </div>

    <!-- Trend -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">AI revenue, last 14 days</div>
      <div v-if="!report.trend?.length" class="text-sm text-slate-500">No data yet.</div>
      <div v-else class="flex items-end gap-1 h-32">
        <div v-for="t in report.trend" :key="t.date" class="flex-1 flex flex-col items-center gap-1 min-w-0">
          <div class="text-[10px] text-slate-500">{{ t.revenue > 0 ? '₹' + t.revenue.toLocaleString('en-IN') : '' }}</div>
          <div class="w-full rounded-t-lg bg-brand-500/80" :style="{ height: barHeight(t.revenue) }" :title="t.date + ': ₹' + t.revenue"></div>
          <div class="text-[9px] text-slate-400 truncate w-full text-center">{{ t.date }}</div>
        </div>
      </div>
    </div>

    <!-- Channels -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">By AI platform (webhook attribution)</div>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="c in report.channels" :key="c.channel" class="rounded-xl border border-slate-200 p-3 text-center">
          <div class="text-sm font-bold">{{ c.label }}</div>
          <div class="text-lg font-extrabold mt-1">{{ c.orders }} <span class="text-xs font-normal text-slate-500">orders</span></div>
          <div class="text-xs text-slate-500">{{ fmt.inr(c.revenue) }}</div>
        </div>
      </div>
    </div>

    <!-- GA4 section -->
    <div class="stat-card">
      <div class="flex items-center justify-between">
        <div class="text-sm font-bold text-slate-900">GA4 Data API — AI traffic from Google Analytics</div>
        <span v-if="ga4.demo" class="badge-amber">Demo data</span>
        <span v-else-if="ga4.configured" class="badge-green">Connected</span>
        <span v-else class="badge-slate">Not configured</span>
      </div>
      <p class="text-xs text-slate-500 mt-1">Sessions, transactions and purchase revenue where the session source is an AI platform (ChatGPT, Gemini, Perplexity, Grok, Claude, DeepSeek, Copilot).</p>

      <div v-if="!ga4.configured" class="mt-3 text-xs bg-slate-50 rounded-xl p-4 leading-relaxed">
        <template v-if="ga4.has_property_id && !ga4.service_account_email">
          <p class="text-slate-600">Your GA4 Property ID is saved. GA4 integration is being set up — data will appear once configured.</p>
        </template>
        <template v-else-if="ga4.has_property_id && ga4.service_account_email">
          <p class="text-slate-600">Your GA4 Property ID is saved. To connect, add this email as a <b>Viewer</b> in your GA4 → Admin → Property access management:</p>
          <code class="block mt-2 bg-amber-100 px-2 py-1 rounded font-bold text-amber-900 select-all text-[11px]">{{ ga4.service_account_email }}</code>
          <p class="text-slate-500 mt-2">After adding, data appears within 24 hours.</p>
        </template>
        <template v-else>
          <p class="text-slate-600">Enter your <b>GA4 Property ID</b> in <b>Settings</b> to see AI traffic from Google Analytics.</p>
          <p class="text-slate-500 mt-1">Find it: GA4 → Admin → Property Settings → Property ID (9-digit number)</p>
        </template>
      </div>

      <template v-else-if="ga4.totals">
        <div class="grid sm:grid-cols-4 gap-3 mt-4">
          <div class="rounded-xl border border-slate-200 p-3 text-center">
            <div class="text-xs text-slate-500">AI sessions</div>
            <div class="text-xl font-extrabold mt-1">{{ (ga4.totals.sessions || 0).toLocaleString('en-IN') }}</div>
          </div>
          <div class="rounded-xl border border-slate-200 p-3 text-center">
            <div class="text-xs text-slate-500">Users</div>
            <div class="text-xl font-extrabold mt-1">{{ (ga4.totals.users || 0).toLocaleString('en-IN') }}</div>
          </div>
          <div class="rounded-xl border border-slate-200 p-3 text-center">
            <div class="text-xs text-slate-500">Transactions</div>
            <div class="text-xl font-extrabold mt-1">{{ (ga4.totals.transactions || 0).toLocaleString('en-IN') }}</div>
          </div>
          <div class="rounded-xl border border-slate-200 p-3 text-center">
            <div class="text-xs text-slate-500">Revenue</div>
            <div class="text-xl font-extrabold mt-1">{{ fmt.inr(ga4.totals.revenue) }}</div>
          </div>
        </div>

        <div v-if="ga4.trend?.length" class="mt-4">
          <div class="text-xs font-bold text-slate-500 mb-2">Daily sessions ({{ ga4.days || 30 }} days)</div>
          <div class="flex items-end gap-0.5 h-24">
            <div v-for="t in ga4.trend" :key="t.date" class="flex-1 flex flex-col items-center min-w-0 group" :title="t.date + ': ' + t.sessions + ' sessions, ₹' + t.revenue">
              <div class="w-full rounded-t bg-slate-300 group-hover:bg-brand-500" :style="{ height: Math.max(3, (t.sessions / ga4MaxSessions) * 100) + '%' }"></div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <div class="text-xs font-bold text-slate-500 mb-2">Top AI sources</div>
          <table class="w-full text-xs">
            <thead>
              <tr class="text-left text-slate-500 border-b border-slate-200">
                <th class="py-1.5">Source</th>
                <th class="py-1.5 text-right">Sessions</th>
                <th class="py-1.5 text-right">Transactions</th>
                <th class="py-1.5 text-right">Revenue</th>
                <th class="py-1.5 text-right">Engagement</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in ga4.sources" :key="s.source" class="border-b border-slate-100 last:border-0">
                <td class="py-1.5 font-semibold">{{ s.source }}</td>
                <td class="py-1.5 text-right">{{ s.sessions.toLocaleString('en-IN') }}</td>
                <td class="py-1.5 text-right">{{ s.transactions }}</td>
                <td class="py-1.5 text-right font-semibold">{{ fmt.inr(s.revenue) }}</td>
                <td class="py-1.5 text-right text-slate-500">{{ s.engagement_rate }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
      <div v-else-if="ga4.error" class="mt-3 text-xs text-red-700 bg-red-50 rounded-xl p-3">{{ ga4.error }}</div>
    </div>

    <!-- Recent orders -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Recent AI-attributed orders</div>
      <div v-if="!report.recent?.length" class="text-sm text-slate-500">No AI orders yet.</div>
      <table v-else class="w-full text-xs">
        <thead>
          <tr class="text-left text-slate-500 border-b border-slate-200">
            <th class="py-2">Order</th>
            <th class="py-2">Channel</th>
            <th class="py-2">UTM source</th>
            <th class="py-2 text-right">Amount</th>
            <th class="py-2 text-right">Date</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="o in report.recent" :key="o.order" class="border-b border-slate-100 last:border-0">
            <td class="py-2 font-semibold">{{ o.order }}</td>
            <td class="py-2"><span class="badge-green">{{ o.channel_label }}</span></td>
            <td class="py-2 text-slate-500">{{ o.utm_source || '—' }}</td>
            <td class="py-2 text-right font-semibold">{{ fmt.inr(o.amount) }}</td>
            <td class="py-2 text-right text-slate-500">{{ fmt.date(o.date) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
      <b>Notes:</b> ChatGPT auto-appends <code class="bg-slate-100 px-1 rounded">utm_source=chatgpt.com</code> to links it sends.
      Free-tier ChatGPT visits may show as <b>Direct</b> (no referrer) in GA4 — our webhook attribution catches many of those anyway,
      so the two numbers together give the full picture.
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { api, fmt } from '../api';

const report = ref({});
const ga4 = ref({});

const bestChannel = computed(() => {
    const channels = report.value.channels || [];
    const best = channels.reduce((a, b) => (b.orders > (a?.orders || 0) ? b : a), null);
    return best && best.orders > 0 ? best : null;
});

const ga4MaxSessions = computed(() => Math.max(1, ...(ga4.value.trend || []).map((t) => t.sessions)));

function barHeight(revenue) {
    const max = Math.max(1, ...(report.value.trend || []).map((t) => t.revenue));
    return Math.max(4, Math.round((revenue / max) * 100)) + '%';
}

onMounted(async () => {
    try {
        report.value = await api.get('/api/attribution');
        ga4.value = await api.get('/api/attribution/ga4');
    } catch (e) { /* session */ }
});
</script>
