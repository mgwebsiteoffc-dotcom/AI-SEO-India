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
            .catch(() => { tokenPromise = null; return 'none'; });
    }
    return tokenPromise;
}

async function request(method, url, body = null) {
    const token = await sessionToken();
    const opts = { method, headers: { Accept: 'application/json' } };
    // Demo mode: the JWT middleware resolves the demo store from the ?demo flag.
    if (window.demoMode && !url.includes('?')) {
        url += '?demo=' + (window.demoValue || '1');
    } else if (window.demoMode) {
        url += '&demo=' + (window.demoValue || '1');
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
        if (window.shopifyApp) {
            window.shopifyApp.redirect({ path: '/auth' }); // re-auth embedded session
        }
        throw new Error('Session expired — please reload');
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
    claude: { label: 'Claude', color: '#d97757' },
    deepseek: { label: 'DeepSeek', color: '#4d6bfe' },
    copilot: { label: 'Microsoft Copilot', color: '#0f6cbd' },
    web: { label: 'AI Retrieval Proxy', color: '#94a3b8' },
};
