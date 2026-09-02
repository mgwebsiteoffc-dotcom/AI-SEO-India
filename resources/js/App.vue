<template>
  <div class="min-h-screen">
    <!-- Top bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
      <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white font-bold">AI</div>
          <div>
            <div class="text-sm font-bold leading-tight">AI Visibility</div>
            <div class="text-[11px] text-slate-500 leading-tight">{{ data.brand || store.shop }}</div>
          </div>
          <span v-if="store.is_demo" class="badge-amber ml-2">Demo</span>
        </div>
        <div class="flex items-center gap-3">
          <span class="badge-slate">{{ store.plan }}</span>
          <button @click="logout" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Exit</button>
        </div>
      </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-6 flex flex-col md:flex-row gap-6">
      <!-- Sidebar -->
      <aside class="md:w-52 shrink-0">
        <nav class="flex md:flex-col gap-1 overflow-x-auto pb-2 md:pb-0">
          <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
                  class="px-3.5 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors"
                  :class="tab === t.key ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100'">
            {{ t.label }}
          </button>
        </nav>
        <div class="hidden md:block mt-6 p-4 rounded-2xl bg-white border border-slate-200 text-xs text-slate-600 space-y-1.5">
          <div class="font-bold text-slate-900">AI Readiness Score</div>
          <div v-if="data.score !== null">
            <div class="text-3xl font-extrabold text-brand-600">{{ data.score }}<span class="text-base text-slate-400">/100</span></div>
            <div class="text-slate-500">Grade {{ data.grade }}</div>
          </div>
          <div v-else class="text-slate-500">Run your first audit to see your score.</div>
          <button @click="$emit('goto', 'audit')" class="mt-2 text-brand-600 font-semibold hover:underline">Run audit →</button>
        </div>
      </aside>

      <!-- Main -->
      <main class="flex-1 min-w-0">
        <Dashboard v-if="tab === 'dashboard'" :data="data" @refresh="load" @goto="tab = $event" />
        <Audit v-else-if="tab === 'audit'" :initial="data" @audited="onAudited" @goto="tab = $event" />
        <BrandSignals v-else-if="tab === 'brand'" />
        <Clients v-else-if="tab === 'clients'" />
        <Tracker v-else-if="tab === 'tracker'" />
        <Content v-else-if="tab === 'content'" />
        <Traffic v-else-if="tab === 'traffic'" />
        <Llms v-else-if="tab === 'llms'" />
        <Schema v-else-if="tab === 'schema'" />
        <Billing v-else-if="tab === 'billing'" />
        <Settings v-else-if="tab === 'settings'" :initial="data" @saved="load" />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { api } from './api';
import Dashboard from './components/Dashboard.vue';
import Audit from './components/Audit.vue';
import BrandSignals from './components/BrandSignals.vue';
import Clients from './components/Clients.vue';
import Tracker from './components/Tracker.vue';
import Content from './components/Content.vue';
import Traffic from './components/Traffic.vue';
import Llms from './components/Llms.vue';
import Schema from './components/Schema.vue';
import Billing from './components/Billing.vue';
import Settings from './components/Settings.vue';

const el = document.getElementById('app');
const tab = ref('dashboard');
const store = reactive({
    shop: el.dataset.shop || '',
    brand: el.dataset.brand || '',
    domain: el.dataset.domain || '',
    plan: el.dataset.plan || 'free',
    is_demo: el.dataset.demo === '1', // dataset read here (module scope) — window.demoMode is set later in app.js
});
const data = reactive({ score: null, grade: null, trend: [], engines: [], store: {} });

const isAgency = computed(() => store.plan === 'agency');
const tabs = computed(() => {
    const list = [
        { key: 'dashboard', label: 'Dashboard' },
        { key: 'audit', label: 'AI Score & Audit' },
        { key: 'brand', label: 'Brand Signals' },
        { key: 'tracker', label: 'AI Visibility Tracker' },
    ];
    if (isAgency.value) {
        list.push({ key: 'clients', label: 'My Clients' });
    }
    list.push(
        { key: 'content', label: 'Smart Blogger' },
        { key: 'traffic', label: 'AI Traffic & Orders' },
        { key: 'llms', label: 'llms.txt' },
        { key: 'schema', label: 'Schema Builder' },
        { key: 'billing', label: 'Plans & Billing' },
        { key: 'settings', label: 'Settings' },
    );
    return list;
});

async function load() {
    try {
        const d = await api.get('/api/dashboard');
        Object.assign(data, d);
        Object.assign(store, d.store || {});
    } catch (e) {
        console.error(e);
    }
}

function onAudited() {
    load();
    tab.value = 'dashboard';
}

function logout() {
    if (window.shopifyApp) {
        window.shopifyApp.redirect({ path: '/auth' });
    } else {
        window.top.location.href = 'https://apps.shopify.com';
    }
}

onMounted(load);
</script>
