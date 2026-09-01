<?php

namespace App\Services;

use App\Models\Store;
use App\Shopify\ShopifyService;
use Illuminate\Support\Facades\Log;

/**
 * Schema Builder — writes JSON-LD structured data via Shopify metafields and
 * injects it on the storefront through the theme app extension.
 */
class SchemaService
{
    /**
     * Write Organization + WebSite JSON-LD to shop-level metafields.
     * Returns an array of results for the UI.
     */
    public function installOrganization(Store $store): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();
        $logo = $this->shopLogo($store);

        $json = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $brand,
            'url' => 'https://'.$domain,
            'logo' => $logo ?: null,
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'availableLanguage' => ['English', 'Hindi'],
            ],
            'areaServed' => 'IN',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $siteJson = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $brand,
            'url' => 'https://'.$domain,
        ], JSON_UNESCAPED_SLASHES);

        $results = [];
        $results[] = $this->writeMetafield($store, 'ai_visibility', 'organization_jsonld', $json, 'json');
        $results[] = $this->writeMetafield($store, 'ai_visibility', 'website_jsonld', $siteJson, 'json');

        return $results;
    }

    /** Write FAQ JSON-LD on a product (from its description/metafields). */
    public function installProductFaq(Store $store, string $productId, array $faqs): array
    {
        $json = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
            ], $faqs),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return [$this->writeProductMetafield($store, $productId, 'faq_jsonld', $json, 'json')];
    }

    public function writeMetafield(Store $store, string $namespace, string $key, string $value, string $type = 'json'): array
    {
        try {
            $client = ShopifyService::client($store);
            $shopGid = $this->shopGid($client);
            if (! $shopGid) {
                return ['ok' => false, 'key' => $key, 'error' => 'Could not resolve shop ID'];
            }
            $query = <<<'GRAPHQL'
            mutation MetafieldsSet($metafields: [MetafieldsSetInput!]!) {
              metafieldsSet(metafields: $metafields) {
                metafields { id key namespace }
                userErrors { field message }
              }
            }
            GRAPHQL;
            $res = $client->query([
                'query' => $query,
                'variables' => [
                    'metafields' => [[
                        'namespace' => $namespace,
                        'key' => $key,
                        'type' => $type,
                        'value' => $value,
                        'ownerId' => $shopGid,
                    ]],
                ],
            ]);
            return ['ok' => true, 'key' => $key, 'response' => $res->getDecodedBody()];
        } catch (\Throwable $e) {
            Log::warning('Metafield write failed: '.$e->getMessage());
            return ['ok' => false, 'key' => $key, 'error' => $e->getMessage()];
        }
    }

    private function shopGid(\Shopify\Clients\Graphql $client): ?string
    {
        try {
            $res = $client->query(['query' => '{ shop { id } }']);
            return $res->getDecodedBody()['data']['shop']['id'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function writeProductMetafield(Store $store, string $productId, string $key, string $value, string $type = 'json'): array
    {
        try {
            $client = ShopifyService::client($store);
            $query = <<<'GRAPHQL'
            mutation MetafieldsSet($metafields: [MetafieldsSetInput!]!) {
              metafieldsSet(metafields: $metafields) {
                metafields { id }
                userErrors { field message }
              }
            }
            GRAPHQL;
            $res = $client->query([
                'query' => $query,
                'variables' => [
                    'metafields' => [[
                        'namespace' => 'ai_visibility',
                        'key' => $key,
                        'type' => $type,
                        'value' => $value,
                        'ownerId' => $productId,
                    ]],
                ],
            ]);
            return ['ok' => true, 'key' => $key, 'response' => $res->getDecodedBody()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'key' => $key, 'error' => $e->getMessage()];
        }
    }

    /** Product JSON-LD (used by the theme extension via the app proxy). */
    public function productJsonLd(array $product): string
    {
        $json = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['title'] ?? '',
            'description' => $product['description'] ?? '',
            'url' => $product['url'] ?? '',
            'image' => $product['image'] ?? null,
            'brand' => ['@type' => 'Brand', 'name' => $product['brand'] ?? ''],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'INR',
                'price' => $product['price'] ?? '0',
                'availability' => ($product['available'] ?? true)
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url' => $product['url'] ?? '',
            ],
            'aggregateRating' => isset($product['rating'])
                ? ['@type' => 'AggregateRating', 'ratingValue' => $product['rating'], 'reviewCount' => $product['review_count'] ?? 0]
                : null,
        ];
        return json_encode(array_filter($json, fn ($v) => $v !== null), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function shopLogo(Store $store): ?string
    {
        try {
            $client = ShopifyService::client($store);
            $res = $client->query(['query' => '{ shop { brand { logo { image { url } } } } }']);
            $url = $res->getDecodedBody()['data']['shop']['brand']['logo']['image']['url'] ?? null;
            return $url;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
