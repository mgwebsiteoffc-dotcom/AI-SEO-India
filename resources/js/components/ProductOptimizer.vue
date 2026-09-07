<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="text-base font-bold text-slate-900">AI Product Optimizer</div>
      <p class="text-xs text-slate-500 mt-1">
        Rewrite your product descriptions to be AI-citation-friendly. Optimized products get recommended more often by ChatGPT, Gemini & Perplexity.
      </p>
    </div>

    <div class="stat-card">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-bold text-slate-900">Optimize all products</div>
        <button @click="optimizeAll" :disabled="optimizing" class="btn-primary text-xs">
          {{ optimizing ? 'Optimizing…' : 'Optimize all products' }}
        </button>
      </div>
      <div v-if="bulkResult" class="text-xs rounded-xl p-3" :class="bulkResult.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
        <div>{{ bulkResult.ok ? `✓ Optimized ${bulkResult.optimized}/${bulkResult.total} products` : `Error: ${bulkResult.error}` }}</div>
        <div v-if="!bulkResult.ok" class="mt-2 text-red-600">
          Tip: Go to the llms.txt tab → click "Generate" to create product entries first.
        </div>
      </div>
    </div>

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
import { ref } from 'vue';
import { api } from '../api';

const optimizing = ref(false);
const optimizingSingle = ref(false);
const bulkResult = ref(null);
const singleResult = ref(null);
const productHandle = ref('');

async function optimizeAll() {
    optimizing.value = true;
    bulkResult.value = null;
    try {
        const result = await api.post('/api/product-optimizer/optimize-all');
        bulkResult.value = result;
    } catch (e) {
        bulkResult.value = { ok: false, error: e.message };
    } finally {
        optimizing.value = false;
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
