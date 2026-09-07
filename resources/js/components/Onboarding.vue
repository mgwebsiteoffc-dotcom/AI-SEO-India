<template>
  <div class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-50 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
      <!-- Logo header -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-brand-600 flex items-center justify-center text-white text-xl font-extrabold mx-auto mb-4 shadow-lg shadow-brand-600/30">AI</div>
        <h1 class="text-2xl font-extrabold text-slate-900">Welcome to AI Visibility</h1>
        <p class="text-sm text-slate-500 mt-1">Let's set up your store in under a minute</p>
      </div>

      <!-- Progress steps -->
      <div class="flex items-center justify-center gap-2 mb-8">
        <div v-for="s in totalSteps" :key="s"
             class="h-1.5 rounded-full transition-all duration-300"
             :class="s <= step ? 'bg-brand-600 w-10' : 'bg-slate-200 w-6'"></div>
      </div>

      <!-- Step content -->
      <div class="stat-card">
        <!-- Step 1: Brand name -->
        <div v-if="step === 1">
          <div class="text-base font-bold text-slate-900 mb-1">What's your brand name?</div>
          <p class="text-xs text-slate-500 mb-5">This is how AI engines will identify your business. Use the name customers know you by.</p>
          <input v-model="form.brand_name" class="input" placeholder="e.g. Aurelia Naturals" autofocus
                 @keyup.enter="form.brand_name && next()" />
          <div class="text-[11px] text-slate-400 mt-2">Leave blank to auto-detect from your Shopify store name.</div>
        </div>

        <!-- Step 2: Domain -->
        <div v-if="step === 2">
          <div class="text-base font-bold text-slate-900 mb-1">Your public website domain</div>
          <p class="text-xs text-slate-500 mb-5">If you have a custom domain, enter it here. Otherwise we'll use your Shopify domain.</p>
          <input v-model="form.domain" class="input" placeholder="e.g. yourbrand.in"
                 @keyup.enter="next()" />
          <div class="flex items-center gap-2 mt-3 p-3 rounded-xl bg-slate-50 text-xs text-slate-600">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>Your Shopify store: <b>{{ shop }}</b></span>
          </div>
        </div>

        <!-- Step 3: Language & WhatsApp -->
        <div v-if="step === 3">
          <div class="text-base font-bold text-slate-900 mb-1">Support preferences</div>
          <p class="text-xs text-slate-500 mb-5">Set your preferred language and optional WhatsApp support number.</p>
          <div class="space-y-4">
            <div>
              <label class="text-xs font-semibold text-slate-600">Language</label>
              <select v-model="form.language" class="input mt-1">
                <option value="en">English</option>
                <option value="hinglish">Hinglish (mix)</option>
                <option value="hi">Hindi</option>
              </select>
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-600">WhatsApp number <span class="text-slate-400">(optional)</span></label>
              <input v-model="form.whatsapp_number" class="input mt-1" placeholder="919876543210" />
              <div class="text-[11px] text-slate-400 mt-1">For support notifications — WhatsApp-first, India-friendly.</div>
            </div>
          </div>
        </div>

        <!-- Step 4: All set -->
        <div v-if="step === 4" class="text-center py-4">
          <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
          </div>
          <div class="text-base font-bold text-slate-900 mb-1">You're all set!</div>
          <p class="text-xs text-slate-500 mb-2">Your store is configured. Here's what happens next:</p>
          <div class="text-left mt-4 space-y-3">
            <div class="flex items-start gap-3 text-xs text-slate-600">
              <div class="w-6 h-6 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center font-bold shrink-0 mt-0.5">1</div>
              <div><b class="text-slate-900">Run your first AI audit</b> — see how AI engines currently see your store</div>
            </div>
            <div class="flex items-start gap-3 text-xs text-slate-600">
              <div class="w-6 h-6 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center font-bold shrink-0 mt-0.5">2</div>
              <div><b class="text-slate-900">Set up llms.txt &amp; schema</b> — help AI engines understand your products</div>
            </div>
            <div class="flex items-start gap-3 text-xs text-slate-600">
              <div class="w-6 h-6 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center font-bold shrink-0 mt-0.5">3</div>
              <div><b class="text-slate-900">Track AI visibility</b> — monitor mentions across ChatGPT, Gemini, Perplexity &amp; more</div>
            </div>
          </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="mt-4 rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-700">
          {{ error }}
        </div>

        <!-- Navigation buttons -->
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-slate-100">
          <button v-if="step > 1" @click="prev" class="btn-secondary text-xs">
            Back
          </button>
          <div v-else></div>
          <button v-if="step < totalSteps" @click="next" class="btn-primary text-xs"
                  :disabled="step === 1 && !form.brand_name.trim()">
            Continue
          </button>
          <button v-else @click="finish" class="btn-primary text-xs" :disabled="saving">
            <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            {{ saving ? 'Setting up…' : 'Launch Dashboard' }}
          </button>
        </div>
      </div>

      <!-- Skip link -->
      <div class="text-center mt-4">
        <button @click="skip" class="text-xs text-slate-400 hover:text-slate-600 underline">
          Skip setup — I'll configure later
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { api } from '../api';

const props = defineProps({
  shop: { type: String, default: '' },
  brand: { type: String, default: '' },
});
const emit = defineEmits(['complete']);

const step = ref(1);
const totalSteps = 4;
const saving = ref(false);
const error = ref('');

const form = reactive({
  brand_name: props.brand || '',
  domain: '',
  language: 'en',
  whatsapp_number: '',
});

function next() {
  if (step.value < totalSteps) {
    step.value++;
    error.value = '';
  }
}

function prev() {
  if (step.value > 1) {
    step.value--;
    error.value = '';
  }
}

async function save() {
  saving.value = true;
  error.value = '';
  try {
    await api.post('/api/onboarding/complete', {
      brand_name: form.brand_name,
      domain: form.domain,
      language: form.language,
      whatsapp_number: form.whatsapp_number,
    });
    return true;
  } catch (e) {
    error.value = e.message || 'Something went wrong. Please try again.';
    return false;
  } finally {
    saving.value = false;
  }
}

async function finish() {
  const ok = await save();
  if (ok) {
    emit('complete');
  }
}

async function skip() {
  // Mark onboarding as completed even when skipping
  await save();
  emit('complete');
}
</script>
