<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="text-base font-bold text-slate-900">Instant Indexing (IndexNow)</div>
      <p class="text-xs text-slate-500 mt-1">
        Notify search engines (Bing, Yandex, Seznam, Naver) about new or updated pages instantly.
        No waiting for crawlers — they get notified within seconds.
      </p>
    </div>

    <div class="stat-card">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-bold text-slate-900">Submit all pages</div>
        <button @click="submitAll" :disabled="submitting" class="btn-primary text-xs">
          {{ submitting ? 'Submitting…' : 'Submit all pages to IndexNow' }}
        </button>
      </div>
      <div v-if="result" class="text-xs rounded-xl p-3" :class="result.ok && result.submitted > 0 ? 'bg-emerald-50 text-emerald-700' : result.ok && result.submitted === 0 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'">
        <div v-if="result.ok && result.submitted > 0">✓ Submitted {{ result.submitted }} pages to IndexNow</div>
        <div v-else-if="result.ok && result.submitted === 0">
          No pages found to submit. Go to the llms.txt tab → click "Generate" first, then try again.
        </div>
        <div v-else>Error: {{ result.error }}</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Submit a single URL</div>
      <form @submit.prevent="submitUrl" class="flex gap-2">
        <input v-model="singleUrl" class="input flex-1 text-sm" placeholder="https://yourdomain.com/products/example" />
        <button type="submit" :disabled="submittingSingle" class="btn-secondary text-xs">
          {{ submittingSingle ? 'Submitting…' : 'Submit URL' }}
        </button>
      </form>
      <div v-if="singleResult" class="text-xs mt-2 rounded-xl p-3" :class="singleResult.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
        {{ singleResult.ok ? '✓ URL submitted to IndexNow' : `Error: ${singleResult.error}` }}
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
      <b>How IndexNow works:</b> When you publish or update content, IndexNow instantly notifies participating search engines.
      This means your new products, blog posts, and pages get discovered much faster than waiting for regular crawling.
      <br><br>
      <b>Note:</b> Google doesn't support IndexNow yet (they use their own Indexing API). For Google, submit your sitemap in Google Search Console.
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { api } from '../api';

const submitting = ref(false);
const submittingSingle = ref(false);
const result = ref(null);
const singleResult = ref(null);
const singleUrl = ref('');

async function submitAll() {
    submitting.value = true;
    result.value = null;
    try {
        result.value = await api.post('/api/indexnow/submit');
    } catch (e) {
        result.value = { ok: false, error: e.message };
    } finally {
        submitting.value = false;
    }
}

async function submitUrl() {
    if (!singleUrl.value.trim()) return;
    submittingSingle.value = true;
    singleResult.value = null;
    try {
        singleResult.value = await api.post('/api/indexnow/submit-url', { url: singleUrl.value });
    } catch (e) {
        singleResult.value = { ok: false, error: e.message };
    } finally {
        submittingSingle.value = false;
    }
}
</script>
