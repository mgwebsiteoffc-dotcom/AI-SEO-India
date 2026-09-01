import { createApp } from 'vue';
import App from './App.vue';

const el = document.getElementById('app');
window.demoMode = el.dataset.demo === '1';

if (!window.demoMode && window.shopify && window.shopify.createApp) {
    window.shopifyApp = window.shopify.createApp({
        apiKey: el.dataset.apiKey,
        host: el.dataset.host,
    });
}

createApp(App).mount('#app');
