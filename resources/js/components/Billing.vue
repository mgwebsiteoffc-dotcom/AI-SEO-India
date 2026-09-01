<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="text-base font-bold text-slate-900">Plans &amp; Billing</div>
          <div class="text-xs text-slate-500 mt-0.5">Priced in ₹ for Indian D2C brands. Cancel anytime. 3-day free trial on paid plans.</div>
        </div>
        <div class="flex items-center gap-1 rounded-xl bg-slate-100 p-1 text-xs font-semibold">
          <button @click="interval = 'monthly'" class="px-3 py-1.5 rounded-lg" :class="interval === 'monthly' ? 'bg-white shadow text-slate-900' : 'text-slate-500'">Monthly</button>
          <button @click="interval = 'annual'" class="px-3 py-1.5 rounded-lg" :class="interval === 'annual' ? 'bg-white shadow text-slate-900' : 'text-slate-500'">Annual <span class="text-emerald-600">−17%</span></button>
        </div>
      </div>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
      <div v-for="p in plans" :key="p.key"
           class="stat-card flex flex-col"
           :class="p.key === current ? 'ring-2 ring-brand-600' : ''">
        <div class="flex items-center justify-between">
          <span class="font-bold text-slate-900">{{ p.name }}</span>
          <span v-if="p.key === current" class="badge-green">Current</span>
        </div>
        <div class="mt-2">
          <span class="text-3xl font-extrabold">{{ displayPrice(p) }}</span>
          <span class="text-xs text-slate-500">{{ p.price === 0 ? '' : interval === 'annual' ? '/year' : '/month' }}</span>
        </div>
        <div v-if="interval === 'annual' && p.price > 0" class="text-[11px] text-emerald-600 font-semibold mt-0.5">Save {{ fmt.inr(p.price * 12 - p.annual_price) }} / year</div>
        <ul class="mt-4 space-y-2 text-xs text-slate-600 flex-1">
          <li v-for="f in p.features" :key="f" class="flex gap-2">
            <span class="text-emerald-600 font-bold">✓</span>{{ f }}
          </li>
        </ul>
        <button v-if="p.key !== current" @click="subscribe(p.key)" :disabled="busy === p.key" class="btn-primary w-full mt-5 text-xs">
          {{ busy === p.key ? 'Opening Shopify checkout…' : p.price === 0 ? 'Downgrade to Free' : 'Start 3-day trial' }}
        </button>
        <button v-else-if="p.key !== 'free'" @click="cancel" class="btn-secondary w-full mt-5 text-xs">Cancel subscription</button>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
      <b>Why it pays for itself:</b> one AI-referred order at ₹1,500 AOV ≈ ₹450 margin. AI-referred visitors convert at ~15.9%
      vs ~1.76% for Google organic. Two or three AI orders a month cover the entire Grow plan.
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api, fmt } from '../api';

const plans = ref([]);
const current = ref('free');
const busy = ref(null);
const interval = ref('monthly');

function displayPrice(p) {
    if (p.price === 0) return '₹0';
    return interval.value === 'annual' ? fmt.inr(p.annual_price) : fmt.inr(p.price);
}

async function load() {
    const d = await api.get('/api/billing/plans');
    plans.value = d.plans;
    current.value = d.current;
}

async function subscribe(key) {
    busy.value = key;
    try {
        const d = await api.post('/api/billing/subscribe', { plan: key, interval: interval.value });
        const url = d.confirmationUrl;
        if (window.shopifyApp) {
            window.shopifyApp.redirect({ path: url });
        } else {
            window.top.location.href = url;
        }
    } catch (e) {
        alert(e.message);
    } finally {
        busy.value = null;
    }
}

async function cancel() {
    if (!confirm('Cancel subscription and downgrade to Free?')) return;
    await api.post('/api/billing/cancel');
    current.value = 'free';
}

onMounted(load);
</script>
