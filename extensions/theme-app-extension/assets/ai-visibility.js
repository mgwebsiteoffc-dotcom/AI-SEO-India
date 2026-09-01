/**
 * AI Visibility theme extension.
 * Injects JSON-LD structured data served through the signed App Proxy
 * (live prices/availability), keeping the storefront lightweight.
 */
(function () {
    if (!window.AIVisibility) return;
    var cfg = window.AIVisibility;
    var proxyBase = cfg.proxyBase || '';

    function inject(json) {
        var script = document.createElement('script');
        script.type = 'application/ld+json';
        script.textContent = JSON.stringify(json);
        document.head.appendChild(script);
    }

    function load(path) {
        var url = proxyBase + '/schema?path=' + encodeURIComponent(path || '/');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('Accept', 'application/ld+json');
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    inject(JSON.parse(xhr.responseText));
                } catch (e) { /* ignore malformed */ }
            }
        };
        xhr.send();
    }

    // Product pages get Product+FAQ JSON-LD; everything else gets Organization/WebSite.
    load(cfg.path || window.location.pathname);
})();
