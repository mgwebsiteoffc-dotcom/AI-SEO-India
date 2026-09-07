<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="text-base font-bold text-slate-900">AI Shopping Feed</div>
      <p class="text-xs text-slate-500 mt-1">
        Generate a structured product feed for AI shopping agents (ChatGPT Shopping, Gemini Shopping, Perplexity Shopping).
        This feed helps AI engines recommend your products when shoppers ask for recommendations.
      </p>
    </div>

    <div class="stat-card">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-bold text-slate-900">Generate shopping feed</div>
        <button @click="generateFeed" :disabled="generating" class="btn-primary text-xs">
          {{ generating ? 'Generating…' : 'Generate feed' }}
        </button>
      </div>

      <div v-if="error" class="rounded-xl bg-red-50 border border-red-200 p-4 text-xs text-red-700 mt-3">
        <div class="font-bold mb-1">Could not generate feed</div>
        <div>{{ error }}</div>
        <div class="mt-2 text-red-600">Tip: Go to the llms.txt tab → click "Generate" to create product entries, then try again.</div>
      </div>

      <div v-if="feed" class="space-y-4">
        <div class="grid sm:grid-cols-3 gap-3">
          <div class="rounded-xl border border-slate-200 p-3 text-center">
            <div class="text-xs text-slate-500">Total products</div>
            <div class="text-xl font-extrabold mt-1">{{ feed.total_products }}</div>
          </div>
          <div class="rounded-xl border border-slate-200 p-3 text-center">
            <div class="text-xs text-slate-500">Store</div>
            <div class="text-sm font-bold mt-1">{{ feed.store?.name }}</div>
          </div>
          <div class="rounded-xl border border-slate-200 p-3 text-center">
            <div class="text-xs text-slate-500">Generated</div>
            <div class="text-xs mt-1">{{ feed.generated_at }}</div>
          </div>
        </div>

        <div>
          <div class="text-xs font-bold text-slate-900 mb-2">Feed preview (first 5 products):</div>
          <div class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-auto max-h-64 font-mono">
            {{ feedPreview }}
          </div>
        </div>

        <div class="flex gap-2">
          <button @click="downloadFeed" class="btn-secondary text-xs">Download JSON</button>
          <button @click="copyFeed" class="btn-secondary text-xs">Copy to clipboard</button>
        </div>
      </div>
    </div>

    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-2">Product feed URL</div>
      <p class="text-xs text-slate-500 mb-2">
        Once generated, your feed is available at this URL. AI shopping agents can access it to get your product data.
      </p>
      <code class="block bg-slate-50 rounded-xl p-3 text-xs text-slate-700 break-all">
        https://aivisibility.akestech.in.net/api/shopping-feed?shop={{ shop }}
      </code>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
      <b>How AI Shopping Feed works:</b>
      <ul class="mt-2 space-y-1 list-disc pl-4">
        <li>AI shopping agents (ChatGPT, Gemini, Perplexity) look for structured product data</li>
        <li>This feed provides product titles, descriptions, prices, and availability in a format AI understands</li>
        <li>When a shopper asks "recommend me products from India", AI can use this feed</li>
        <li>Feed updates automatically when you regenerate it after adding new products</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { api } from '../api';

const generating = ref(false);
const feed = ref(null);
const shop = ref('');

const feedPreview = computed(() => {
    if (!feed.value?.products) return '';
    const preview = {
        store: feed.value.store,
        products: feed.value.products.slice(0, 5),
        total_products: feed.value.total_products,
    };
    return JSON.stringify(preview, null, 2);
});

const error = ref('');

async function generateFeed() {
    generating.value = true;
    feed.value = null;
    error.value = '';
    try {
        const result = await api.get('/api/shopping-feed');
        if (result.ok) {
            feed.value = result.feed;
        } else {
            error.value = result.error || 'Failed to generate feed';
        }
    } catch (e) {
        error.value = e.message;
    } finally {
        generating.value = false;
    }
}

function downloadFeed() {
    if (!feed.value) return;
    const blob = new Blob([JSON.stringify(feed.value, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'shopping-feed.json';
    a.click();
    URL.revokeObjectURL(url);
}

function copyFeed() {
    if (!feed.value) return;
    navigator.clipboard.writeText(JSON.stringify(feed.value, null, 2));
    alert('Feed copied to clipboard!');
}
</script>
