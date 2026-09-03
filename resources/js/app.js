import { createApp } from 'vue';
import App from './App.vue';

const el = document.getElementById('app');
window.demoMode = el.dataset.demo === '1';

// App Bridge initialization — must have a valid host param (base64-encoded
// shop admin domain). If host is missing or empty, App Bridge cannot
// generate session tokens and all API calls will 401.
const host = el.dataset.host || '';
const apiKey = el.dataset.apiKey || '';

if (!window.demoMode && apiKey) {
    if (window.shopify && window.shopify.createApp && host) {
        try {
            window.shopifyApp = window.shopify.createApp({ apiKey, host });
            console.log('[AI Visibility] App Bridge initialized');
        } catch (e) {
            console.warn('[AI Visibility] App Bridge init failed:', e);
        }
    } else {
        console.warn('[AI Visibility] App Bridge unavailable — host:', host || '(empty)', 'shopify:', !!window.shopify);
    }
}

createApp(App).mount('#app');
