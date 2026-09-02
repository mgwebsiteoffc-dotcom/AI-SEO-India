<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="text-base font-bold text-slate-900">llms.txt — the AI reading list</div>
          <div class="text-xs text-slate-500 mt-0.5">
            A machine-readable index of your store at <code class="bg-slate-100 px-1 rounded">{{ proxyUrl || '…/llms.txt' }}</code>
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="toggle" class="btn-secondary text-xs">
            {{ enabled ? 'Disable' : 'Enable' }}
          </button>
          <button @click="generate" :disabled="busy" class="btn-primary text-xs">
            {{ busy ? 'Building…' : 'Generate / refresh' }}
          </button>
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800 leading-relaxed">
      <b>Honest note:</b> as of 2026 no major AI engine has confirmed it reads llms.txt (studies show ~97% of files get zero
      crawler requests). We include it because it costs nothing and may matter for agentic browsers — but it is <i>hygiene</i>,
      not the headline. Your real AI visibility comes from schema, crawlability and citation-ready content, which this app also fixes.
    </div>

    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-2">Preview — {{ entries.length }} entries</div>
      <pre class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-auto max-h-96 leading-relaxed">{{ content || 'Generate to preview…' }}</pre>
    </div>

    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-1">agent.md — AI-agent manual</div>
      <div class="text-xs text-slate-500 mb-3">
        A storefront "operating manual" for AI agents and agentic browsers at
        <code class="bg-slate-100 px-1 rounded">{{ agentUrl || '…/agent.md' }}</code> — what you sell, key pages, schema and support contact.
      </div>
      <pre class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-auto max-h-64 leading-relaxed">{{ agent || '…' }}</pre>
    </div>

    <div class="stat-card">
      <div class="text-sm font-bold text-slate-900 mb-2">robots.txt advisory — allow AI crawlers</div>
      <pre class="bg-slate-900 text-slate-100 text-xs rounded-xl p-4 overflow-auto max-h-64 leading-relaxed">{{ robots || '…' }}</pre>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';

const enabled = ref(false);
const entries = ref([]);
const proxyUrl = ref('');
const agentUrl = ref('');
const content = ref('');
const robots = ref('');
const agent = ref('');
const busy = ref(false);

async function load() {
    const d = await api.get('/api/llms');
    enabled.value = d.enabled;
    entries.value = d.entries || [];
    proxyUrl.value = d.proxy_url || '';
    agentUrl.value = d.agent_url || '';
    if (!content.value) {
        content.value = entries.value.map((e) => `- [${e.title}](https://${e.path})`).join('\n');
    }
}

async function fetchProxyFile(path) {
    const url = '/apps/ai-visibility/' + path + (window.demoMode ? '?demo=1' : '');
    const r = await fetch(url, { headers: { Accept: 'text/plain' } });
    return r.ok ? r.text() : null;
}

async function generate() {
    busy.value = true;
    try {
        const d = await api.post('/api/llms/generate');
        content.value = d.content;
        await load();
        try {
            agent.value = (await fetchProxyFile('agent.md')) || '';
        } catch (e) { /* preview optional */ }
    } catch (e) {
        alert(e.message);
    } finally {
        busy.value = false;
    }
}

async function toggle() {
    const d = await api.post('/api/llms/toggle', { enabled: !enabled.value });
    enabled.value = d.enabled;
}

onMounted(async () => {
    try {
        await load();
        robots.value = (await fetchProxyFile('robots.txt')) || '';
    } catch (e) { /* proxy not resolvable in preview */ }
    try {
        agent.value = (await fetchProxyFile('agent.md')) || '';
    } catch (e) { /* proxy not resolvable in preview */ }
});
</script>
