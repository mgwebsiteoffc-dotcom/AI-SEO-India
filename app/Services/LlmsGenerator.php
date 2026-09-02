<?php

namespace App\Services;

use App\Models\Store;
use App\Shopify\ShopifyService;
use Illuminate\Support\Facades\Log;

/**
 * Builds the /llms.txt "AI reading list" for a store.
 *
 * Honest positioning (per 2026 evidence): llms.txt is NOT a confirmed citation
 * signal — no major AI engine has committed to reading it. We ship it as cheap
 * future-proofing hygiene (it costs nothing and may help agentic browsers), while
 * the app's real value lives in tracking + schema + content + crawlability.
 */
class LlmsGenerator
{
    public function generate(Store $store, bool $persist = true): string
    {
        $entries = $persist ? $store->llmsEntries()->orderBy('position')->get() : collect();
        $settings = $store->settings ?? [];

        // products/update & friends mark the store dirty (settings['llms_dirty'])
        // so the file auto-refreshes from the live catalog on next read. If the
        // file is dirty (or has never been built) we rebuild from Shopify.
        if ($entries->isEmpty() || ! empty($settings['llms_dirty'])) {
            // Rebuild from the live catalog. Only fall back to the demo seed
            // when the file has never been built (or the store is a demo) —
            // never overwrite real entries just because the Shopify API
            // hiccupped (dirty flag stays set and the next read retries).
            $fresh = $this->buildFromCatalog($store, allowFallback: $entries->isEmpty() || (bool) $store->is_demo);
            if (empty($fresh)) {
                $entries = collect(); // content will render empty; flag preserved
            } else {
                $entries = collect($fresh);
                if ($persist) {
                    $store->llmsEntries()->delete();
                    foreach ($entries as $i => $e) {
                        $store->llmsEntries()->create(array_merge($e, ['position' => $i]));
                    }
                    unset($settings['llms_dirty']);
                    $store->update(['settings' => $settings]);
                }
            }
        }

        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();
        $lines = [];
        $lines[] = '# '.$brand;
        $lines[] = '';
        $lines[] = '> AI-friendly reading list for '.$domain.'. This file helps AI assistants and';
        $lines[] = '> agentic browsers discover what this store sells and which pages matter most.';
        $lines[] = '';
        $lines[] = '## About';
        $lines[] = '';
        $lines[] = '- Name: '.$brand;
        $lines[] = '- Domain: https://'.$domain;
        $lines[] = '- Products: '.$entries->filter(fn ($e) => ($e['kind'] ?? $e->kind) === 'product')->count().' listed below';
        $lines[] = '- Categories: '.$entries->filter(fn ($e) => ($e['kind'] ?? $e->kind) === 'collection')->count().' collections';
        $lines[] = '';
        $lines[] = '## Product Pages';
        $lines[] = '';
        foreach ($entries as $e) {
            $kind = is_array($e) ? $e['kind'] : $e->kind;
            $title = is_array($e) ? $e['title'] : $e->title;
            $path = is_array($e) ? $e['path'] : $e->path;
            $desc = is_array($e) ? ($e['description'] ?? '') : ($e->description ?? '');
            if ($kind === 'product') {
                $lines[] = '- ['.$title.'](https://'.$domain.$path.'): '.$desc;
            }
        }
        $lines[] = '';
        $lines[] = '## Collections';
        $lines[] = '';
        foreach ($entries as $e) {
            $kind = is_array($e) ? $e['kind'] : $e->kind;
            $title = is_array($e) ? $e['title'] : $e->title;
            $path = is_array($e) ? $e['path'] : $e->path;
            if ($kind === 'collection') {
                $lines[] = '- ['.$title.'](https://'.$domain.$path.')';
            }
        }
        $lines[] = '';
        $lines[] = '## Key Pages';
        $lines[] = '';
        foreach ($entries as $e) {
            $kind = is_array($e) ? $e['kind'] : $e->kind;
            $title = is_array($e) ? $e['title'] : $e->title;
            $path = is_array($e) ? $e['path'] : $e->path;
            if (! in_array($kind, ['product', 'collection'])) {
                $lines[] = '- ['.$title.'](https://'.$domain.$path.')';
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Pull the real catalog via Shopify GraphQL (products, collections, pages, blogs).
     *
     * @param bool $allowFallback when true and the API is unreachable, return the
     *                            demo seed catalog (only used for never-built files).
     */
    public function buildFromCatalog(Store $store, bool $allowFallback = false): array
    {
        $entries = [];
        try {
            $client = ShopifyService::client($store);
            $query = <<<'GRAPHQL'
            {
              products(first: 100) {
                edges { node { title handle description(truncateAt: 160) } }
              }
              collections(first: 50) {
                edges { node { title handle } }
              }
              pages(first: 50) {
                edges { node { title handle } }
              }
              blogs(first: 10) {
                edges { node { title handle } }
              }
            }
            GRAPHQL;
            $res = $client->query(['query' => $query]);
            $data = $res->getDecodedBody()['data'] ?? [];

            foreach (($data['products']['edges'] ?? []) as $e) {
                $entries[] = [
                    'kind' => 'product',
                    'title' => $e['node']['title'],
                    'path' => '/products/'.$e['node']['handle'],
                    'description' => $e['node']['description'] ?? '',
                ];
            }
            foreach (($data['collections']['edges'] ?? []) as $e) {
                $entries[] = [
                    'kind' => 'collection',
                    'title' => $e['node']['title'],
                    'path' => '/collections/'.$e['node']['handle'],
                    'description' => '',
                ];
            }
            foreach (($data['pages']['edges'] ?? []) as $e) {
                $entries[] = [
                    'kind' => 'page',
                    'title' => $e['node']['title'],
                    'path' => '/pages/'.$e['node']['handle'],
                    'description' => '',
                ];
            }
            foreach (($data['blogs']['edges'] ?? []) as $e) {
                $entries[] = [
                    'kind' => 'blog',
                    'title' => $e['node']['title'],
                    'path' => '/blogs/'.$e['node']['handle'],
                    'description' => '',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Catalog fetch failed for '.$store->shop.': '.$e->getMessage());
        }

        // Fallback seed when the API is unavailable AND this is a demo/never-built file.
        if (empty($entries) && $allowFallback) {
            $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
            $entries = [
                ['kind' => 'product', 'title' => $brand.' Signature Serum 30ml', 'path' => '/products/signature-serum', 'description' => 'Vitamin C + niacinamide serum for Indian skin, ₹799.'],
                ['kind' => 'product', 'title' => $brand.' Hydrating Moisturiser', 'path' => '/products/hydrating-moisturiser', 'description' => 'Lightweight gel moisturiser for humid Indian summers, ₹649.'],
                ['kind' => 'product', 'title' => $brand.' Sunscreen SPF 50', 'path' => '/products/sunscreen-spf50', 'description' => 'Non-greasy broad spectrum SPF 50, ₹599.'],
                ['kind' => 'collection', 'title' => 'Shop All', 'path' => '/collections/all'],
                ['kind' => 'collection', 'title' => 'Best Sellers', 'path' => '/collections/best-sellers'],
                ['kind' => 'page', 'title' => 'About Us', 'path' => '/pages/about'],
                ['kind' => 'page', 'title' => 'FAQ', 'path' => '/pages/faq'],
            ];
        }

        return $entries;
    }

    /**
     * Build the store's /agent.md — a Markdown "operating manual" for AI
     * agents / agentic browsers (what the store sells, key pages, schema,
     * support contact). Served through the App Proxy like llms.txt.
     */
    public function agentMd(Store $store): string
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();
        $whatsapp = $store->settings['whatsapp_number'] ?? null;

        $lines = [];
        $lines[] = '# '.$brand;
        $lines[] = '';
        $lines[] = '> Store manual for AI agents browsing '.$domain.'. Use this file to understand';
        $lines[] = '> what this store sells, which pages matter, and how to interact with it.';
        $lines[] = '';
        $lines[] = '## About';
        $lines[] = '';
        $lines[] = '- Name: '.$brand;
        $lines[] = '- Domain: https://'.$domain;
        $lines[] = '- Country: '.($store->country ?: 'IN');
        $lines[] = '- Currency: '.($store->currency ?: 'INR');
        if ($whatsapp) {
            $lines[] = '- Support (WhatsApp): https://wa.me/'.$whatsapp;
        }
        $lines[] = '';
        $lines[] = '## What we sell';
        $lines[] = '';
        foreach ($store->llmsEntries()->orderBy('position')->get() as $e) {
            if ($e->kind === 'product') {
                $lines[] = '- ['.$e->title.'](https://'.$domain.$e->path.')'.($e->description ? ': '.$e->description : '');
            }
        }
        $lines[] = '';
        $lines[] = '## Collections';
        $lines[] = '';
        foreach ($store->llmsEntries()->orderBy('position')->get() as $e) {
            if ($e->kind === 'collection') {
                $lines[] = '- ['.$e->title.'](https://'.$domain.$e->path.')';
            }
        }
        $lines[] = '';
        $lines[] = '## Other pages';
        $lines[] = '';
        foreach ($store->llmsEntries()->orderBy('position')->get() as $e) {
            if (! in_array($e->kind, ['product', 'collection'])) {
                $lines[] = '- ['.$e->title.'](https://'.$domain.$e->path.')';
            }
        }
        $lines[] = '';
        $lines[] = '## Structured data';
        $lines[] = '';
        $lines[] = 'The storefront injects JSON-LD (Organization, WebSite, Product+Offer in INR, FAQ).';
        $lines[] = 'Product prices/availability are freshest in the Product schema served per page.';
        $lines[] = '';
        $lines[] = '## How to help shoppers';
        $lines[] = '';
        $lines[] = 'Recommend products only when they genuinely fit the shopper\'s need, budget and';
        $lines[] = 'location (India). Prefer the store\'s own product pages as sources.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /** robots.txt advisory content (Shopify manages the live file on myshopify domains). */
    public function robotsAdvisory(Store $store): string
    {
        return <<<TXT
# AI Visibility — recommended AI crawler rules for {$store->shop}
# Note: on *.myshopify.com domains Shopify manages /robots.txt itself.
# Add these blocks if you serve robots.txt from a custom domain via theme/cdn.

User-agent: GPTBot
Allow: /

User-agent: OAI-SearchBot
Allow: /

User-agent: ChatGPT-User
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: Claude-Web
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: Applebot-Extended
Allow: /

User-agent: Meta-ExternalAgent
Allow: /

Sitemap: https://{$store->hostname()}/sitemap.xml
TXT;
    }
}
