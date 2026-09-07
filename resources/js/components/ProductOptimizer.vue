<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="text-base font-bold text-slate-900">AI Product Optimizer</div>
      <p class="text-xs text-slate-500 mt-1">
        Rewrite your product descriptions to be AI-citation-friendly. Optimized products get recommended more often by ChatGPT, Gemini & Perplexity.
      </p>
    </div>

    <!-- Optimize button -->
    <div class="stat-card">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-bold text-slate-900">Optimize all products</div>
        <div class="flex gap-2">
          <button @click="loadPending" :disabled="loadingPending" class="btn-secondary text-xs">
            {{ loadingPending ? 'Loading…' : 'Load pending' }}
          </button>
          <button @click="optimizeAll" :disabled="optimizing" class="btn-primary text-xs">
            {{ optimizing ? 'Optimizing…' : 'Optimize all products' }}
          </button>
        </div>
      </div>
      <div v-if="bulkResult" class="text-xs rounded-xl p-3" :class="bulkResult.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
        <div>{{ bulkResult.ok ? `✓ Optimized ${bulkResult.optimized}/${bulkResult.total} products — review and approve below` : `Error: ${bulkResult.error}` }}</div>
        <div v-if="!bulkResult.ok" class="mt-2 text-red-600">
          Tip: Go to the llms.txt tab → click "Generate" to create product entries first.
        </div>
      </div>
    </div>

    <!-- Product list with approve/reject -->
    <div v-if="products.length" class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Optimized products ({{ products.length }})</div>
      <div class="space-y-4">
        <div v-for="(p, i) in products" :key="p.handle" class="rounded-xl border border-slate-200 overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between p-4 bg-slate-50">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-8 h-8 rounded-lg bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                {{ i + 1 }}
              </div>
              <div class="min-w-0">
                <div class="text-sm font-bold text-slate-900 truncate">{{ p.product }}</div>
                <div class="text-[11px] text-slate-500">
                  {{ p.handle }} · {{ p.price ? '₹' + p.price : 'No price' }} · {{ p.word_count }} words
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span v-if="p.status === 'approved'" class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Approved</span>
              <span v-else-if="p.status === 'rejected'" class="text-[11px] font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Rejected</span>
              <template v-else-if="p.optimized">
                <button @click="approve(p.handle, 'approved')" class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-800 px-2 py-1 rounded-lg hover:bg-emerald-50">
                  ✓ Approve
                </button>
                <button @click="approve(p.handle, 'rejected')" class="text-[11px] font-semibold text-red-600 hover:text-red-800 px-2 py-1 rounded-lg hover:bg-red-50">
                  ✗ Reject
                </button>
              </template>
              <button @click="expanded = expanded === i ? null : i" class="text-xs text-slate-500 hover:text-slate-700 px-2 py-1">
                {{ expanded === i ? 'Hide' : 'View' }}
              </button>
            </div>
          </div>

          <!-- Expanded content -->
          <div v-if="expanded === i" class="p-4 border-t border-slate-200">
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase mb-1">Original</div>
                <div class="bg-slate-50 rounded-lg p-3 text-xs text-slate-600 max-h-48 overflow-auto">
                  {{ p.original_description || 'No description' }}
                </div>
              </div>
              <div>
                <div class="text-[11px] font-bold text-brand-600 uppercase mb-1">AI-Optimized</div>
                <div class="bg-brand-50 rounded-lg p-3 text-xs text-slate-800 max-h-48 overflow-auto whitespace-pre-wrap leading-relaxed">
                  {{ p.optimized_description || 'Not optimized' }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Single product optimizer -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Optimize a single product</div>
      <div class="flex gap-2 mb-4">
        <input v-model="productHandle" class="input flex-1 text-sm" placeholder="Product handle (e.g. pastel-blue-shirt)" />
        <button @click="optimizeSingle" :disabled="optimizingSingle" class="btn-secondary text-xs">
          {{ optimizingSingle ? 'Optimizing…' : 'Optimize' }}
        </button>
      </div>

      <div v-if="singleResult" class="space-y-3">
        <div class="text-xs font-bold text-slate-900">Optimized description:</div>
        <div class="bg-slate-50 rounded-xl p-4 text-xs text-slate-700 whitespace-pre-wrap max-h-96 overflow-auto leading-relaxed">{{ singleResult.optimized }}</div>
        <div class="text-xs text-slate-500">Word count: {{ singleResult.word_count }}</div>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
      <b>Why optimize product descriptions?</b>
      <ul class="mt-2 space-y-1 list-disc pl-4">
        <li>AI engines prefer structured, detailed product information</li>
        <li>Optimized descriptions include FAQ sections that AI quotes directly</li>
        <li>₹ pricing and India-specific context helps local AI recommendations</li>
        <li>Clear specifications make it easier for AI to compare products</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';

const optimizing = ref(false);
const optimizingSingle = ref(false);
const loadingPending = ref(false);
const bulkResult = ref(null);
const singleResult = ref(null);
const productHandle = ref('');
const products = ref([]);
const expanded = ref(null);

onMounted(loadPending);

async function loadPending() {
    loadingPending.value = true;
    try {
        const result = await api.get('/api/product-optimizer/pending');
        products.value = result.results || [];
    } catch (e) {
        // Ignore — no pending optimizations yet
    } finally {
        loadingPending.value = false;
    }
}

async function optimizeAll() {
    optimizing.value = true;
    bulkResult.value = null;
    try {
        const result = await api.post('/api/product-optimizer/optimize-all');
        bulkResult.value = result;
        if (result.ok) {
            products.value = result.results || [];
        }
    } catch (e) {
        bulkResult.value = { ok: false, error: e.message };
    } finally {
        optimizing.value = false;
    }
}

async function approve(handle, action) {
    try {
        await api.post('/api/product-optimizer/approve', { handle, action });
        // Update local state
        const p = products.value.find(p => p.handle === handle);
        if (p) p.status = action;
    } catch (e) {
        alert(e.message);
    }
}

async function optimizeSingle() {
    if (!productHandle.value.trim()) return;
    optimizingSingle.value = true;
    singleResult.value = null;
    try {
        singleResult.value = await api.post('/api/product-optimizer/optimize', {
            handle: productHandle.value,
        });
    } catch (e) {
        alert(e.message);
    } finally {
        optimizingSingle.value = false;
    }
}
</script>
