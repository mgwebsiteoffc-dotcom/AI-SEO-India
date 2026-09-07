<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Log;

/**
 * AI Shopping Feed — generate a structured product feed for AI shopping agents.
 *
 * AI shopping agents (ChatGPT Shopping, Gemini Shopping, Perplexity Shopping)
 * need structured product data to recommend products. This service generates
 * a feed in the format these agents expect.
 */
class ShoppingFeedService
{
    /**
     * Generate an AI-optimized shopping feed for the store.
     */
    public function generate(Store $store): array
    {
        $domain = $store->hostname();
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));

        // Fetch products from Shopify
        $products = $this->fetchProducts($store);

        if (empty($products)) {
            return [
                'ok' => false,
                'error' => 'No products found. The app needs read_products scope. Please reinstall the app from Shopify admin to grant this permission, or go to the llms.txt tab and click "Generate" first.',
            ];
        }

        // Build the feed
        $feed = [
            'store' => [
                'name' => $brand,
                'domain' => "https://{$domain}",
                'currency' => 'INR',
                'country' => 'IN',
                'language' => 'en',
            ],
            'products' => [],
            'generated_at' => now()->toIso8601String(),
            'total_products' => count($products),
        ];

        foreach ($products as $product) {
            $feed['products'][] = [
                'id' => $product['id'],
                'title' => $product['title'],
                'description' => $product['description'],
                'price' => [
                    'amount' => $product['price'],
                    'currency' => 'INR',
                    'formatted' => '₹' . number_format($product['price'], 0, '.', ','),
                ],
                'availability' => $product['available'] ? 'in_stock' : 'out_of_stock',
                'url' => "https://{$domain}/products/{$product['handle']}",
                'image' => $product['image'] ?? null,
                'brand' => $brand,
                'category' => $product['type'] ?? null,
                'tags' => $product['tags'] ?? [],
                'variants' => $product['variants'] ?? [],
            ];
        }

        return [
            'ok' => true,
            'feed' => $feed,
            'json' => json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * Generate a JSON-LD formatted feed for schema.org Product markup.
     */
    public function generateJsonLd(Store $store): string
    {
        $domain = $store->hostname();
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $products = $this->fetchProducts($store);

        $jsonLd = [];
        foreach ($products as $product) {
            $jsonLd[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product['title'],
                'description' => $product['description'],
                'brand' => [
                    '@type' => 'Brand',
                    'name' => $brand,
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $product['price'],
                    'priceCurrency' => 'INR',
                    'availability' => $product['available']
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock',
                    'url' => "https://{$domain}/products/{$product['handle']}",
                ],
                'image' => $product['image'] ?? null,
                'url' => "https://{$domain}/products/{$product['handle']}",
            ];
        }

        return json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Fetch products from Shopify GraphQL API.
     * Falls back to llms entries if Shopify API fails.
     */
    private function fetchProducts(Store $store): array
    {
        try {
            $client = \App\Shopify\ShopifyService::client($store);
            $res = $client->query([
                'query' => <<<'GRAPHQL'
                {
                  products(first: 50) {
                    edges {
                      node {
                        id
                        title
                        handle
                        description(truncateAt: 300)
                        productType
                        tags
                        availableForSale
                        priceRange {
                          minVariantPrice {
                            amount
                            currencyCode
                          }
                        }
                        featuredImage {
                          url
                        }
                      }
                    }
                  }
                }
                GRAPHQL,
            ]);

            $body = $res->getDecodedBody();
            if (!empty($body['errors'])) {
                Log::warning('Shopping feed product fetch errors', ['errors' => $body['errors']]);
                return $this->fallbackFromLlms($store);
            }

            $products = [];
            foreach (($body['data']['products']['edges'] ?? []) as $edge) {
                $node = $edge['node'];
                $price = 0;
                if (isset($node['priceRange']['minVariantPrice']['amount'])) {
                    $price = (float) $node['priceRange']['minVariantPrice']['amount'];
                }
                $products[] = [
                    'id' => $node['id'] ?? '',
                    'title' => $node['title'] ?? '',
                    'handle' => $node['handle'] ?? '',
                    'description' => $node['description'] ?? '',
                    'price' => $price,
                    'available' => $node['availableForSale'] ?? false,
                    'image' => $node['featuredImage']['url'] ?? null,
                    'type' => $node['productType'] ?? null,
                    'tags' => $node['tags'] ?? [],
                ];
            }

            // If Shopify returned no products, try llms entries
            if (empty($products)) {
                return $this->fallbackFromLlms($store);
            }

            return $products;
        } catch (\Throwable $e) {
            Log::warning('Shopping feed fetch failed: ' . $e->getMessage());
            return $this->fallbackFromLlms($store);
        }
    }

    /**
     * Fallback: build product list from llms entries.
     */
    private function fallbackFromLlms(Store $store): array
    {
        $products = [];
        foreach ($store->llmsEntries()->where('kind', 'product')->take(50)->get() as $entry) {
            $products[] = [
                'id' => $entry->id,
                'title' => $entry->title,
                'handle' => basename($entry->path),
                'description' => $entry->description ?? '',
                'price' => 0,
                'available' => true,
                'image' => null,
                'type' => null,
                'tags' => [],
            ];
        }

        // If still empty, generate llms entries first
        if (empty($products)) {
            try {
                app(\App\Services\LlmsGenerator::class)->generate($store, persist: true);
                foreach ($store->llmsEntries()->where('kind', 'product')->take(50)->get() as $entry) {
                    $products[] = [
                        'id' => $entry->id,
                        'title' => $entry->title,
                        'handle' => basename($entry->path),
                        'description' => $entry->description ?? '',
                        'price' => 0,
                        'available' => true,
                        'image' => null,
                        'type' => null,
                        'tags' => [],
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Llms fallback failed: ' . $e->getMessage());
            }
        }

        return $products;
    }
}
