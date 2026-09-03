// API client for the embedded app
const TOKEN_TTL = 1000 * 60 * 55; // refresh session token before 1h expiry

let tokenPromise = null;

function sessionToken() {
    if (window.demoMode) {
        return Promise.resolve('demo');
    }
    if (!window.shopifyApp) {
        return Promise.resolve('none');
    }
    if (!tokenPromise) {
        tokenPromise = window.shopifyApp.idToken()
            .then((t) => {
                setTimeout(() => { tokenPromise = null; }, TOKEN_TTL);
                return t;
            })
            .catch((err) => {
                console.warn('[AI Visibility] Session token error:', err);
                tokenPromise = null;
                return 'none';
            });
    }
    return tokenPromise;
}

/** Get the shop domain from the page — used as fallback auth when JWT is unavailable. */
function getShopDomain() {
    return document.getElementById('app')?.dataset?.shop || '';
}

async function request(method, url, body = null) {
    const token = await sessionToken();
    const opts = { method, headers: { Accept: 'application/json' } };

    // Always pass shop domain as query param so the backend can resolve the
    // store even when App Bridge / JWT is not available (first load, expired
    // token, missing host param, etc.).
    const params = new URLSearchParams();
    if (window.demoMode) {
        params.set('demo', '1');
    }
    const shop = getShopDomain();
    if (shop) {
        params.set('shop', shop);
    }
    const qs = params.toString();
    if (qs) {
        url += (url.includes('?') ? '&' : '?') + qs;
    }

    if (token !== 'none') {
        opts.headers.Authorization = `Bearer ${token}`;
    }
    if (body) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }

    const res = await fetch(url, opts);

    if (res.status === 401) {
        // Don't redirect to /auth in a loop — just throw with a clear message.
        // The caller (App.vue / Onboarding.vue) decides what to show.
        throw new Error('Session expired — please reload the app');
    }
    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data.error || data.message || `Request failed (${res.status})`);
    }
    return res.json();
}

export const api = {
    get: (url) => request('GET', url),
    post: (url, body = {}) => request('POST', url, body),
    del: (url) => request('DELETE', url),
};

export const fmt = {
    inr: (n) => '₹' + Number(n || 0).toLocaleString('en-IN'),
    pct: (n) => (n === null || n === undefined ? '—' : Math.round(n) + '%'),
    date: (iso) => (iso ? new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'),
};

export const severityBadge = {
    critical: 'badge-red',
    warning: 'badge-amber',
    info: 'badge-slate',
};

export const engineMeta = {
    chatgpt: { label: 'ChatGPT', color: '#10a37f' },
    gemini: { label: 'Gemini', color: '#4285f4' },
    perplexity: { label: 'Perplexity', color: '#20b8cd' },
    grok: { label: 'Grok', color: '#111827' },
    deepseek: { label: 'DeepSeek', color: '#4d6bfe' },
    web: { label: 'AI Retrieval Proxy', color: '#94a3b8' },
};
