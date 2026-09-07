<template>
  <div class="space-y-5">
    <div class="stat-card">
      <div class="text-base font-bold text-slate-900">Content Calendar</div>
      <p class="text-xs text-slate-500 mt-1">
        Schedule blog posts in advance and get AI-powered content ideas. Posts auto-publish to your Shopify blog at the scheduled time.
      </p>
    </div>

    <!-- Scheduled posts -->
    <div class="stat-card">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-bold text-slate-900">Scheduled posts ({{ calendar.length }})</div>
        <button @click="loadCalendar" class="btn-secondary text-xs">Refresh</button>
      </div>

      <div v-if="!calendar.length" class="text-sm text-slate-500 py-3">
        No scheduled posts. Generate content in Smart Blogger, then schedule it here.
      </div>

      <div class="space-y-3">
        <div v-for="post in calendar" :key="post.id" class="rounded-xl border border-slate-200 p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-sm font-bold text-slate-900">{{ post.title }}</div>
              <div class="text-[11px] text-slate-500 mt-0.5">
                {{ post.keyword }} · {{ post.category }} · {{ post.status }}
              </div>
              <div class="text-xs text-brand-600 mt-1">
                Scheduled: {{ formatDate(post.scheduled_at) }}
              </div>
            </div>
            <button @click="cancelSchedule(post)" class="text-xs font-semibold text-red-500 hover:text-red-700">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Content ideas -->
    <div class="stat-card">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-bold text-slate-900">Content ideas</div>
        <button @click="generateIdeas" :disabled="generatingIdeas" class="btn-primary text-xs">
          {{ generatingIdeas ? 'Generating…' : 'Get AI ideas' }}
        </button>
      </div>

      <div v-if="ideas.length" class="space-y-3">
        <div v-for="(idea, i) in ideas" :key="i" class="rounded-xl border border-slate-200 p-4">
          <div class="text-sm font-bold text-slate-900">{{ idea.title }}</div>
          <div class="text-xs text-slate-500 mt-1">Keyword: {{ idea.keyword }} · Category: {{ idea.category }}</div>
          <div class="text-xs text-slate-600 mt-2">{{ idea.reason }}</div>
          <button @click="createFromIdea(idea)" class="btn-primary !py-1.5 text-xs mt-2">
            Generate article
          </button>
        </div>
      </div>

      <div v-else class="text-sm text-slate-500 py-3">
        Click "Get AI ideas" to generate content ideas based on your store and trending topics.
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-600 leading-relaxed">
      <b>Content Calendar tips:</b>
      <ul class="mt-2 space-y-1 list-disc pl-4">
        <li>Schedule 2-3 posts per week for consistent AI visibility</li>
        <li>Focus on comparison and guide articles — they get cited most by AI</li>
        <li>Include ₹ pricing and India-specific context in every article</li>
        <li>Use the FAQ section — AI engines quote FAQs directly</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';

const calendar = ref([]);
const ideas = ref([]);
const generatingIdeas = ref(false);

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function loadCalendar() {
    try {
        const result = await api.get('/api/content/calendar');
        calendar.value = result.posts || [];
    } catch (e) {
        console.error(e);
    }
}

async function cancelSchedule(post) {
    if (!confirm('Cancel this scheduled post?')) return;
    try {
        await api.post(`/api/content/${post.id}/unschedule`);
        await loadCalendar();
    } catch (e) {
        alert(e.message);
    }
}

async function generateIdeas() {
    generatingIdeas.value = true;
    ideas.value = [];
    try {
        const result = await api.get('/api/content/ideas');
        ideas.value = result.ideas || [];
    } catch (e) {
        alert(e.message);
    } finally {
        generatingIdeas.value = false;
    }
}

async function createFromIdea(idea) {
    try {
        await api.post('/api/content/generate', {
            keyword: idea.keyword,
            category: idea.category,
            tone: 'informative',
        });
        alert('Article generated! Go to Smart Blogger to review and publish.');
    } catch (e) {
        alert(e.message);
    }
}

onMounted(loadCalendar);
</script>
