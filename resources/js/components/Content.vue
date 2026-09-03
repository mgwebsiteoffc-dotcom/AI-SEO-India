<template>
  <div class="space-y-5">
    <!-- Sentiment card -->
    <div class="stat-card">
      <div class="flex items-center justify-between">
        <div class="text-sm font-bold text-slate-900">AI Sentiment — how do AI answers feel about your brand?</div>
        <button @click="runSentiment" :disabled="sentimentBusy" class="btn-secondary text-xs">
          {{ sentimentBusy ? 'Reading AI answers…' : 'Run sentiment' }}
        </button>
      </div>
      <div v-if="sentiment.available === false" class="mt-3 text-xs text-amber-700 bg-amber-50 rounded-xl p-3 leading-relaxed">
        {{ sentiment.message || 'AI Sentiment needs an API key.' }}
      </div>
      <div v-else-if="sentiment.available" class="mt-4 grid md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 p-4">
          <div class="text-xs text-slate-500">Overall sentiment</div>
          <div class="text-2xl font-extrabold mt-1 capitalize" :class="sentimentColor">{{ sentiment.sentiment }}</div>
          <div class="text-xs text-slate-500 mt-1">Score: {{ sentiment.score }}/100</div>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
          <div class="text-xs text-slate-500">Themes AI associates with you</div>
          <div class="flex flex-wrap gap-1.5 mt-2">
            <span v-for="t in sentiment.themes" :key="t" class="badge-slate">{{ t }}</span>
          </div>
        </div>
        <div class="rounded-xl border border-slate-200 p-4 text-xs text-slate-600 leading-relaxed">
          <div class="text-slate-500 font-semibold">Advice</div>
          <p class="mt-1">{{ sentiment.advice || '—' }}</p>
          <p v-if="sentiment.quote" class="mt-2 italic text-slate-500">“{{ sentiment.quote }}”</p>
        </div>
      </div>
      <div v-else class="mt-3 text-xs text-slate-500">No sentiment run yet.</div>
    </div>

    <!-- Generate -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Smart Blogger — content AI engines will cite</div>
      <form @submit.prevent="generate" class="grid sm:grid-cols-2 gap-3">
        <div class="sm:col-span-2">
          <input v-model="keyword" required placeholder='Keyword, e.g. "best sunscreen for oily skin under 800"' class="input" />
        </div>
        <select v-model="category" class="input">
          <option value="guide">Guide</option>
          <option value="comparison">Comparison</option>
          <option value="product">Product roundup</option>
          <option value="category">Category explainer</option>
        </select>
        <select v-model="tone" class="input">
          <option value="informative">Indian English (informative)</option>
          <option value="hindi-english">Hinglish (desi, relatable)</option>
          <option value="youthful">Gen-Z Indian</option>
        </select>
        <button type="submit" class="btn-primary sm:col-span-2" :disabled="busy">
          {{ busy ? 'Writing article…' : (llmMode ? 'Generate with AI ✨' : 'Generate (template mode — add API key for AI-written copy)') }}
        </button>
      </form>
      <p v-if="!llmMode" class="text-[11px] text-slate-500 mt-2">
        No LLM API key configured — using the honest template engine. Add an OpenRouter, OpenAI, or Gemini key in super admin → AI Settings for fully AI-written copy.
      </p>
    </div>

    <!-- Articles -->
    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-3">Your articles ({{ posts.length }})</div>
      <div v-if="!posts.length" class="text-sm text-slate-500 py-3">No articles yet — generate your first one above. Articles are built from your real catalog and can be published to your Shopify blog in one click.</div>
      <div class="space-y-3">
        <div v-for="p in posts" :key="p.id" class="rounded-xl border border-slate-200 p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-sm font-bold text-slate-900">{{ p.title }}</div>
              <div class="text-[11px] text-slate-500 mt-0.5">
                {{ p.keyword }} · {{ p.category }} · {{ p.tone }} · {{ p.word_count }} words · <span :class="statusBadge[p.status] || 'badge-slate'">{{ p.status }}</span>
              </div>
            </div>
            <div class="flex gap-2 shrink-0">
              <button @click="preview = preview === p.id ? null : p.id" class="btn-secondary !py-1.5 text-xs">Preview</button>
              <button v-if="p.status !== 'published'" @click="publish(p)" :disabled="publishing === p.id" class="btn-primary !py-1.5 text-xs">
                {{ publishing === p.id ? 'Publishing…' : 'Publish to blog' }}
              </button>
              <button @click="remove(p)" class="text-xs font-semibold text-red-500 hover:text-red-700">Delete</button>
            </div>
          </div>
          <a v-if="p.article_url" :href="p.article_url" target="_blank" class="text-xs text-brand-600 font-semibold mt-2 inline-block">Live on your blog →</a>
          <div v-if="preview === p.id" class="mt-3">
            <div class="bg-slate-50 rounded-xl p-4 text-xs text-slate-700 whitespace-pre-wrap max-h-80 overflow-auto leading-relaxed">{{ p.body }}</div>
            <div v-if="p.faqs?.length" class="mt-3">
              <div class="text-[11px] font-bold text-slate-500 mb-1.5">FAQ schema-ready questions:</div>
              <div v-for="f in p.faqs" :key="f.question" class="text-xs text-slate-600 py-1 border-b border-slate-100 last:border-0">
                <b>{{ f.question }}</b> — {{ f.answer }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';

const posts = ref([]);
const keyword = ref('');
const category = ref('guide');
const tone = ref('informative');
const busy = ref(false);
const publishing = ref(null);
const preview = ref(null);
const llmMode = ref(false);
const sentiment = ref({});
const sentimentBusy = ref(false);
const statusBadge = { draft: 'badge-slate', generated: 'badge-amber', published: 'badge-green', failed: 'badge-red' };
const sentimentColor = { positive: 'text-emerald-600', mixed: 'text-amber-600', neutral: 'text-slate-600', negative: 'text-red-600' };

async function load() {
    const d = await api.get('/api/content');
    posts.value = d.posts || [];
}

async function generate() {
    busy.value = true;
    try {
        await api.post('/api/content/generate', { keyword: keyword.value, category: category.value, tone: tone.value });
        keyword.value = '';
        await load();
    } catch (e) {
        alert(e.message);
    } finally {
        busy.value = false;
    }
}

async function publish(p) {
    publishing.value = p.id;
    try {
        const d = await api.post(`/api/content/${p.id}/publish`);
        if (!d.ok) {
            alert('Publish failed: ' + (d.error || 'unknown'));
        }
        await load();
    } catch (e) {
        alert(e.message);
    } finally {
        publishing.value = null;
    }
}

async function remove(p) {
    if (!confirm('Delete this article?')) return;
    await api.del(`/api/content/${p.id}`);
    await load();
}

async function runSentiment() {
    sentimentBusy.value = true;
    try {
        sentiment.value = await api.get('/api/content/sentiment');
    } catch (e) {
        alert(e.message);
    } finally {
        sentimentBusy.value = false;
    }
}

onMounted(async () => {
    try {
        await load();
        const t = await api.get('/api/tracker');
        llmMode.value = t.llm_mode;
    } catch (e) { /* session */ }
});
</script>
