<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\LlmsGenerator;
use App\Services\SchemaService;
use App\Shopify\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * App Proxy endpoints — served by Shopify on the store's own domain at
 * https://{store}/apps/ai-visibility/* so they look first-party.
 * Protected by the App Proxy signature middleware.
 */
class ProxyController extends Controller
{
    private function store(Request $request): ?Store
    {
        return $request->attributes->get('store');
    }

    /** GET .../llms.txt → the AI reading list (auto-refreshes when stale) */
    public function llmsTxt(Request $request)
    {
        $store = $this->store($request);
        if (! $store) {
            return response('Not found', 404);
        }
        // persist:true rebuilds from the live catalog when products changed
        // (llms_dirty flag) or the file was never built — see LlmsGenerator.
        $content = app(LlmsGenerator::class)->generate($store, persist: true);
        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** GET .../agent.md → AI-agent manual for the store */
    public function agentMd(Request $request)
    {
        $store = $this->store($request);
        if (! $store) {
            return response('Not found', 404);
        }
        // Refresh first (same persistence rules as llms.txt) so agents always
        // see the latest catalog.
        app(LlmsGenerator::class)->generate($store, persist: true);
        $content = app(LlmsGenerator::class)->agentMd($store);
        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** GET .../robots.txt → advisory AI-crawler rules (view this from the app UI) */
    public function robotsTxt(Request $request)
    {
        $store = $this->store($request);
        if (! $store) {
            return response('Not found', 404);
        }
        return response(app(LlmsGenerator::class)->robotsAdvisory($store), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /** GET .../sitemap.xml → AI-oriented sitemap built from the llms entries */
    public function sitemapXml(Request $request)
    {
        $store = $this->store($request);
        if (! $store) {
            return response('Not found', 404);
        }
        $domain = $store->hostname();
        $entries = $store->llmsEntries()->orderBy('position')->get();
        if ($entries->isEmpty()) {
            app(LlmsGenerator::class)->generate($store, persist: true);
            $entries = $store->llmsEntries()->orderBy('position')->get();
        }
        $urls = '';
        foreach ($entries as $e) {
            $urls .= "  <url><loc>https://{$domain}{$e->path}</loc><changefreq>weekly</changefreq></url>\n";
        }
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$urls}</urlset>\n";
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /** GET .../schema?path=/products/x → JSON-LD the theme extension injects */
    public function schema(Request $request)
    {
        $store = $this->store($request);
        if (! $store) {
            return response('Not found', 404);
        }
        $path = (string) $request->query('path', '/');
        $domain = $store->hostname();
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));

        if (preg_match('#^/products/([a-z0-9\-]+)#i', $path, $m)) {
            $handle = $m[1];
            $product = $this->fetchProduct($store, $handle);

            // Demo store without live Shopify data → serve the seeded catalog
            // entry so the theme-extension flow is fully previewable.
            if (! $product && $store->is_demo) {
                $product = $this->demoProduct($store, $handle);
            }

            if ($product) {
                $product['url'] = "https://{$domain}{$path}";
                $product['brand'] = $brand;
                $json = app(SchemaService::class)->productJsonLd($product);
                return response($json, 200, ['Content-Type' => 'application/ld+json']);
            }
        }

        // Default: Organization + WebSite
        $json = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'Organization', 'name' => $brand, 'url' => "https://{$domain}"],
                ['@type' => 'WebSite', 'name' => $brand, 'url' => "https://{$domain}"],
            ],
        ], JSON_UNESCAPED_SLASHES);
        return response($json, 200, ['Content-Type' => 'application/ld+json']);
    }

    /** Demo fallback: product from the seeded llms catalog (no live store needed). */
    private function demoProduct(Store $store, string $handle): ?array
    {
        $entry = \App\Models\LlmsEntry::query()
            ->where('store_id', $store->id)
            ->where('kind', 'product')
            ->where('path', 'like', "%/products/{$handle}")
            ->first();

        if (! $entry) {
            return null;
        }

        preg_match('/[₹]\s*(\d+(?:\.\d+)?)/u', (string) $entry->description, $m);

        return [
            'title' => $entry->title,
            'description' => $entry->description,
            'image' => null,
            'price' => $m[1] ?? '999',
            'available' => true,
            'rating' => null,
            'review_count' => 0,
        ];
    }

    private function fetchProduct(Store $store, string $handle): ?array
    {
        try {
            $client = ShopifyService::client($store);
            $res = $client->query([
                'query' => <<<'GRAPHQL'
                query ProductByHandle($handle: String!) {
                  productByHandle(handle: $handle) {
                    title
                    description(truncateAt: 400)
                    featuredImage { url }
                    priceRange { minVariantPrice { amount } }
                    availableForSale
                    rating: metafield(namespace: "ai_visibility", key: "rating_value") { value }
                    reviews: metafield(namespace: "ai_visibility", key: "review_count") { value }
                  }
                }
                GRAPHQL,
                'variables' => ['handle' => $handle],
            ]);
            $p = $res->getDecodedBody()['data']['productByHandle'] ?? null;
            if (! $p) {
                return null;
            }
            return [
                'title' => $p['title'],
                'description' => $p['description'] ?? '',
                'image' => $p['featuredImage']['url'] ?? null,
                'price' => $p['priceRange']['minVariantPrice']['amount'] ?? '0',
                'available' => $p['availableForSale'] ?? true,
                'rating' => $p['rating']['value'] ?? null,
                'review_count' => $p['reviews']['value'] ?? 0,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
