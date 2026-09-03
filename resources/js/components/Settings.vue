<template>
  <div class="space-y-5 max-w-xl">
    <div class="stat-card">
      <div class="text-base font-bold text-slate-900 mb-4">Store settings</div>
      <form @submit.prevent="save" class="space-y-4">
        <div>
          <label class="text-xs font-semibold text-slate-600">Brand name (as AI should know you)</label>
          <input v-model="brand" class="input mt-1" placeholder="e.g. Aurelia Naturals" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-600">Public domain</label>
          <input v-model="domain" class="input mt-1" placeholder="yourbrand.in (leave blank for myshopify.com)" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-600">WhatsApp support number (91xxxxxxxxxx)</label>
          <input v-model="whatsapp" class="input mt-1" placeholder="919876543210" />
          <div class="text-[11px] text-slate-500 mt-1">Shown as the support channel in the app — WhatsApp-first, Hinglish-friendly.</div>
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-600">Support language</label>
          <select v-model="language" class="input mt-1">
            <option value="en">English</option>
            <option value="hinglish">Hinglish (mix)</option>
            <option value="hi">Hindi</option>
          </select>
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-600">GA4 Property ID (optional)</label>
          <input v-model="ga4Id" class="input mt-1" placeholder="e.g. 123456789" />
          <div class="text-[11px] text-slate-500 mt-1">
            Your Google Analytics 4 Property ID (9-digit number). Find it in GA4 → Admin → Property Settings.
            Enables AI traffic data from Google Analytics.
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-600">Google Search Console Property (optional)</label>
          <input v-model="gscProperty" class="input mt-1" placeholder="e.g. https://yourdomain.com or sc-domain:yourdomain.com" />
          <div class="text-[11px] text-slate-500 mt-1">
            Your Search Console property URL. Enables search performance data (impressions, clicks, queries).
          </div>
        </div>
        <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
          <span>Shopify store</span>
          <span class="font-semibold">{{ shop }}</span>
        </div>
        <button type="submit" class="btn-primary text-xs">Save settings</button>
      </form>
    </div>

    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-2">Daily AI snapshot</div>
      <p class="text-xs text-slate-600 leading-relaxed">
        The tracker runs automatically every day at 6:00 AM IST via <code class="bg-slate-100 px-1 rounded">visibility:track --all</code>.
        Add an OpenAI or Gemini API key in <code class="bg-slate-100 px-1 rounded">.env</code> to upgrade from retrieval-proxy mode
        to real LLM answer checking (see README).
      </p>
    </div>

    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-2">Install &amp; tech</div>
      <p class="text-xs text-slate-600 leading-relaxed">
        Native Shopify app: embedded admin (App Bridge + Vue), OAuth, App Proxy (llms.txt / robots.txt / sitemap.xml / schema),
        webhooks (app/uninstalled, products/update, orders/paid), recurring INR billing, Laravel 12 backend. Theme app extension
        injects JSON-LD on the storefront. Full setup guide in <code class="bg-slate-100 px-1 rounded">README.md</code>.
      </p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';

const props = defineProps({ initial: Object });
const emit = defineEmits(['saved']);

const brand = ref('');
const domain = ref('');
const shop = ref('');
const whatsapp = ref('');
const language = ref('en');
const ga4Id = ref('');
const gscProperty = ref('');

onMounted(async () => {
    try {
        const d = await api.get('/api/settings');
        brand.value = d.brand_name || props.initial.store?.brand || '';
        domain.value = d.domain && !d.domain.includes('myshopify') ? d.domain : '';
        shop.value = d.shop || props.initial.store?.shop || '';
        whatsapp.value = d.whatsapp_number || '';
        language.value = d.language || 'en';
        ga4Id.value = d.ga4_property_id || '';
        gscProperty.value = d.gsc_property || '';
    } catch (e) { /* session */ }
});

async function save() {
    await api.post('/api/settings', {
        brand_name: brand.value,
        domain: domain.value,
        whatsapp_number: whatsapp.value,
        language: language.value,
        ga4_property_id: ga4Id.value,
        gsc_property: gscProperty.value,
    });
    emit('saved');
    alert('Saved ✓');
}
</script>
