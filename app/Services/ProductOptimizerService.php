<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI Product Description Optimizer
 *
 * Rewrites product descriptions to be AI-citation-friendly:
 * - Structured format with clear specifications
 * - Includes pricing, availability, shipping info
 * - FAQ-ready format for AI answers
 * - India-specific context (₹ pricing, COD, delivery times)
 */
class ProductOptimizerService
{
    public function __construct(private LlmClient $llm) {}

    /**
     * Optimize a single product description for AI citation.
     */
    public function optimize(Store $store, array $product): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();

        $system = <<<PROMPT
You are an e-commerce content specialist for Indian D2C brands. You rewrite product descriptions to be optimized for AI assistants (ChatGPT, Gemini, Perplexity) that answer shopping queries.

Rules for AI-optimized descriptions:
1. Start with a clear, concise product title and brand name
2. Include key specifications in a structured format (bullet points)
3. Include exact pricing in ₹ with any discounts
4. Mention availability, delivery time, and COD availability
5. Include 2-3 FAQ-style Q&A at the end
6. Use Indian English with India-specific context
7. Include the product URL naturally
8. Keep total length 200-400 words
9. Use markdown formatting for structure

Output format:
# [Product Title] — [Brand]

[2-3 sentence hook that answers "what is this product and who is it for"]

## Key Features
- Feature 1
- Feature 2
- Feature 3

## Specifications
| Spec | Value |
|------|-------|
| Price | ₹XXX |
| ... | ... |

## Why [Brand]?
[1-2 sentences about brand trust, quality, Indian market fit]

## FAQ
**Q: [Common question]**
A: [Direct answer]

**Q: [Another question]**
A: [Direct answer]

Shop: https://{$domain}
PROMPT;

        $productInfo = "Product: {$product['title']}\n";
        $productInfo .= "Description: {$product['description']}\n";
        $productInfo .= "Price: ₹{$product['price']}\n";
        $productInfo .= "Available: " . ($product['available'] ? 'Yes' : 'No') . "\n";
        $productInfo .= "URL: https://{$domain}/products/{$product['handle']}\n";

        $optimized = $this->llm->chat($system, $productInfo);

        if ($optimized === null) {
            // Fallback: template-based optimization
            return $this->templateOptimize($store, $product);
        }

        return [
            'ok' => true,
            'original' => $product['description'],
            'optimized' => $optimized,
            'word_count' => str_word_count(strip_tags($optimized)),
        ];
    }

    /**
     * Template-based optimization when LLM is not available.
     */
    private function templateOptimize(Store $store, array $product): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();
        $title = $product['title'];
        $price = $product['price'];
        $description = $product['description'];
        $available = $product['available'] ? 'In Stock' : 'Out of Stock';

        $optimized = <<<MD
# {$title} — {$brand}

{$description}

## Key Features
- Premium quality from {$brand}
- Designed for Indian consumers
- Available exclusively at {$domain}

## Specifications
| Spec | Value |
|------|-------|
| Price | ₹{$price} |
| Availability | {$available} |
| Brand | {$brand} |
| Delivery | Pan-India, 3-7 business days |
| COD | Available |

## Why {$brand}?
{$brand} is an Indian D2C brand focused on quality and customer satisfaction. Shop with confidence with easy returns and WhatsApp support.

## FAQ
**Q: How much does {$title} cost?**
A: {$title} is priced at ₹{$price}. Check the product page for current offers.

**Q: Does {$brand} offer COD?**
A: Yes, Cash on Delivery is available on most orders across India.

**Q: What is the delivery time?**
A: Standard delivery takes 3-7 business days across India.

Shop now: https://{$domain}/products/{$product['handle']}
MD;

        return [
            'ok' => true,
            'original' => $description,
            'optimized' => $optimized,
            'word_count' => str_word_count(strip_tags($optimized)),
        ];
    }

    /**
     * Bulk optimize all products for a store.
     */
    public function optimizeAll(Store $store, int $limit = 10): array
    {
        $catalog = app(SmartBlogger::class)->catalogProducts($store, $limit);

        if (empty($catalog)) {
            return [
                'ok' => false,
                'error' => 'No products found. The app needs read_products scope to access your products. Please reinstall the app from Shopify admin, or generate llms.txt first (go to llms.txt tab → Generate).',
                'total' => 0,
                'optimized' => 0,
                'results' => [],
            ];
        }

        $results = [];

        foreach ($catalog as $product) {
            $result = $this->optimize($store, $product);
            $results[] = [
                'product' => $product['title'],
                'handle' => $product['handle'],
                'price' => $product['price'] ?? 0,
                'original_description' => Str::limit($product['description'] ?? '', 200),
                'optimized_description' => $result['optimized'] ?? null,
                'optimized' => $result['ok'] ?? false,
                'word_count' => $result['word_count'] ?? 0,
                'status' => 'pending',
            ];
        }

        // Store the optimization results for later approval
        $settings = $store->settings ?? [];
        $settings['pending_optimizations'] = $results;
        $store->update(['settings' => $settings]);

        return [
            'ok' => true,
            'total' => count($results),
            'optimized' => collect($results)->where('optimized', true)->count(),
            'results' => $results,
        ];
    }

    /**
     * Approve or reject an optimized product description.
     */
    public function approveOptimization(Store $store, string $handle, string $action): array
    {
        $settings = $store->settings ?? [];
        $pending = $settings['pending_optimizations'] ?? [];
        $found = false;

        foreach ($pending as &$item) {
            if ($item['handle'] === $handle) {
                $item['status'] = $action;
                $found = true;
                break;
            }
        }

        if (!$found) {
            return ['ok' => false, 'error' => 'Product not found in pending optimizations'];
        }

        $settings['pending_optimizations'] = $pending;
        $store->update(['settings' => $settings]);

        return ['ok' => true, 'handle' => $handle, 'status' => $action];
    }

    /**
     * Get pending optimizations for a store.
     */
    public function getPendingOptimizations(Store $store): array
    {
        $settings = $store->settings ?? [];
        return $settings['pending_optimizations'] ?? [];
    }
}
