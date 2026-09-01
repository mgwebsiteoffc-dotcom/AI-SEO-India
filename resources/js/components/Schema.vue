<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="text-base font-bold text-slate-900">Schema Builder</div>
          <div class="text-xs text-slate-500 mt-0.5">
            JSON-LD structured data — one of the strongest, evidence-backed AI-citation signals
          </div>
        </div>
        <button @click="install" :disabled="busy || installed" class="btn-primary text-xs">
          {{ installed ? 'Installed ✓' : busy ? 'Installing…' : 'Install Organization schema' }}
        </button>
      </div>
      <div class="mt-3 flex flex-wrap gap-2">
        <span v-for="f in features" :key="f" class="badge" :class="installed ? 'badge-green' : 'badge-slate'">{{ f }}</span>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
      <div class="stat-card">
        <div class="text-sm font-bold text-slate-900 mb-2">Organization + WebSite</div>
        <pre class="bg-slate-900 text-slate-100 text-[11px] rounded-xl p-4 overflow-auto max-h-72 leading-relaxed">{{ orgJson }}</pre>
      </div>
      <div class="stat-card">
        <div class="text-sm font-bold text-slate-900 mb-2">Product + FAQ (per product)</div>
        <pre class="bg-slate-900 text-slate-100 text-[11px] rounded-xl p-4 overflow-auto max-h-72 leading-relaxed">{{ productJson }}</pre>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
      <b>How it's injected:</b> metafields are written to your shop via the Admin API, and the theme app extension renders them as
      <code class="bg-slate-100 px-1 rounded">&lt;script type="application/ld+json"&gt;</code> on the storefront. No theme code edits needed.
      Product schema is served through the signed app proxy so it always shows live prices and availability.
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';

const installed = ref(false);
const busy = ref(false);
const features = ['Organization', 'WebSite', 'Product', 'FAQ', 'Offer / INR pricing', 'AggregateRating'];

const orgJson = `{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Your Brand",
  "url": "https://yourbrand.in",
  "contactPoint": { "contactType": "customer service" },
  "areaServed": "IN"
}`;

const productJson = `{
  "@type": "Product",
  "name": "Vitamin C Serum 30ml",
  "offers": { "priceCurrency": "INR", "price": "799" },
  "aggregateRating": { "ratingValue": 4.6, "reviewCount": 312 }
}`;

const status = computed(() => (installed.value ? 'badge-green' : 'badge-slate'));

async function load() {
    const d = await api.get('/api/schema/status');
    installed.value = d.installed;
}

async function install() {
    busy.value = true;
    try {
        const d = await api.post('/api/schema/install');
        installed.value = d.ok;
        if (!d.ok) alert('Install failed — see response: ' + JSON.stringify(d.results));
    } catch (e) {
        alert(e.message);
    } finally {
        busy.value = false;
    }
}

onMounted(load);
</script>
