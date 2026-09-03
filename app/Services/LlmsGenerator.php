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

        if ($entries->isEmpty()) {
            // Build from the Shopify catalog via GraphQL
            $built = $this->buildFromCatalog($store);
            $entries = collect($built);
            if ($persist) {
                $store->llmsEntries()->delete();
                foreach ($built as $i => $e) {
                    $store->llmsEntries()->create(array_merge($e, ['position' => $i]));
                }
                $entries = $store->llmsEntries()->orderBy('position')->get();
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
        $lines[] = '- Products: '.$entries->filter(fn ($e) => (is_array($e) ? $e['kind'] : $e->kind) === 'product')->count().' listed below';
        $lines[] = '- Categories: '.$entries->filter(fn ($e) => (is_array($e) ? $e['kind'] : $e->kind) === 'collection')->count().' collections';
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

    /** Pull the real catalog via Shopify GraphQL (products, collections, pages, blogs). */
    public function buildFromCatalog(Store $store): array
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
            $body = $res->getDecodedBody();

            if (! empty($body['errors'])) {
                Log::warning('LlmsGenerator catalog fetch errors', ['errors' => $body['errors'], 'shop' => $store->shop]);
            }

            $data = $body['data'] ?? [];

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

        // When the API is unavailable, show a minimal entry with just the brand info
        // instead of misleading dummy products that don't match the store's catalog
        if (empty($entries)) {
            $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
            $entries = [
                ['kind' => 'page', 'title' => $brand, 'path' => '/', 'description' => 'Store homepage.'],
                ['kind' => 'page', 'title' => 'All Products', 'path' => '/collections/all', 'description' => 'Browse all products.'],
            ];
        }

        return $entries;
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
