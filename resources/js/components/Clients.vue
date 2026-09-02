<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="text-base font-bold text-slate-900">My Clients</div>
          <div class="text-xs text-slate-500 mt-0.5">
            Agency plan — manage every store you look after from one dashboard, and hand each client a
            white-label AI Visibility report link.
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <input v-model="newShop" type="text" class="input !w-64" placeholder="client-store.myshopify.com"
                 @keyup.enter="invite" />
          <button @click="invite" :disabled="adding" class="btn-primary text-xs">{{ adding ? 'Adding…' : 'Add client' }}</button>
        </div>
      </div>
      <div v-if="demoMode" class="text-[11px] text-amber-700 bg-amber-50 rounded-lg p-2 mt-3">
        Demo mode: stores are added locally. In production an install link is generated and the merchant is
        attributed to you after they approve the app.
      </div>
    </div>

    <div v-if="clients.length === 0" class="stat-card text-sm text-slate-500">
      No client stores yet — add the first <code class="text-brand-600 font-mono">*.myshopify.com</code> above.
    </div>

    <div v-for="c in clients" :key="c.id" class="stat-card">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="min-w-0">
          <div class="text-sm font-bold text-slate-900 flex items-center gap-2 flex-wrap">
            {{ c.brand }}
            <span class="badge-slate text-[10px]">{{ c.shop }}</span>
          </div>
          <div class="text-xs text-slate-500 mt-1">{{ c.domain || 'no custom domain yet' }}</div>
        </div>
        <div class="flex items-center gap-5 flex-wrap text-center">
          <div>
            <div class="text-xl font-extrabold text-brand-600">{{ c.audit_score ?? '—' }}</div>
            <div class="text-[10px] uppercase tracking-wide text-slate-500">AI Readiness</div>
          </div>
          <div>
            <div class="text-xl font-extrabold text-slate-800">{{ c.brand_score ?? '—' }}</div>
            <div class="text-[10px] uppercase tracking-wide text-slate-500">Brand Signals</div>
          </div>
          <div>
            <div class="text-xl font-extrabold text-slate-800">{{ c.mention_rate === null ? '—' : c.mention_rate + '%' }}</div>
            <div class="text-[10px] uppercase tracking-wide text-slate-500">Mention rate</div>
          </div>
          <div class="flex flex-col gap-1.5 items-start">
            <a v-if="c.report_url" :href="c.report_url" target="_blank" class="btn text-[11px] !py-1.5">View client report ↗</a>
            <span v-else class="text-[11px] text-slate-400 italic">no report link yet</span>
            <button @click="detach(c)" class="text-[11px] text-red-600 hover:text-red-700 font-semibold">Remove client</button>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
      <b>White-label reports:</b> each client’s report link is branded with your agency name and hides the AI
      Visibility watermark while white-label mode is on (toggle it in Settings). Removing a client immediately
      revokes the link.
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';

const clients = ref([]);
const newShop = ref('');
const adding = ref(false);
const demoMode = ref(false);

async function load() {
    try {
        const d = await api.get('/api/clients');
        clients.value = d.clients || [];
    } catch (e) {
        alert(e.message);
    }
}

async function invite() {
    const shop = newShop.value.trim().toLowerCase();
    if (!/^[a-z0-9-]+\.myshopify\.com$/.test(shop)) {
        alert('Enter a valid store domain, e.g. your-client.myshopify.com');
        return;
    }
    adding.value = true;
    try {
        const d = await api.post('/api/clients/invite', { shop });
        demoMode.value = !!d.demo;
        newShop.value = '';
        await load();
    } catch (e) {
        alert(e.message);
    } finally {
        adding.value = false;
    }
}

async function detach(c) {
    if (!confirm(`Remove ${c.brand} from your clients? Their report link will stop working.`)) return;
    try {
        await api.delete(`/api/clients/${c.id}`);
        await load();
    } catch (e) {
        alert(e.message);
    }
}

onMounted(load);
</script>
