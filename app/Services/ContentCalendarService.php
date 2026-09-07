<?php

namespace App\Services;

use App\Models\ContentPost;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Content Calendar — schedule and auto-publish blog posts.
 *
 * Features:
 * - Schedule posts for future publication
 * - Auto-publish to Shopify blog at scheduled time
 * - Content ideas based on trending topics
 * - Bulk scheduling
 */
class ContentCalendarService
{
    /**
     * Get the content calendar for a store.
     */
    public function getCalendar(Store $store, int $days = 30): array
    {
        $posts = $store->contentPosts()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays($days))
            ->orderBy('scheduled_at')
            ->get();

        return [
            'ok' => true,
            'posts' => $posts->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'keyword' => $p->keyword,
                'scheduled_at' => $p->scheduled_at?->toIso8601String(),
                'status' => $p->status,
                'category' => $p->category,
            ]),
            'total' => $posts->count(),
        ];
    }

    /**
     * Schedule a post for future publication.
     */
    public function schedule(Store $store, int $postId, string $scheduledAt): array
    {
        $post = $store->contentPosts()->findOrFail($postId);

        $scheduled = \Carbon\Carbon::parse($scheduledAt);
        if ($scheduled->isPast()) {
            return ['ok' => false, 'error' => 'Scheduled time must be in the future'];
        }

        $post->update([
            'scheduled_at' => $scheduled,
            'status' => 'scheduled',
        ]);

        return [
            'ok' => true,
            'post_id' => $post->id,
            'scheduled_at' => $scheduled->toIso8601String(),
        ];
    }

    /**
     * Publish all posts that are due (called by scheduler).
     */
    public function publishDue(): array
    {
        $posts = ContentPost::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $published = 0;
        $failed = 0;

        foreach ($posts as $post) {
            $store = $post->store;
            if (!$store) {
                $failed++;
                continue;
            }

            try {
                $result = app(SmartBlogger::class)->publish($store, $post);
                if ($result['ok']) {
                    $published++;
                } else {
                    $failed++;
                    Log::warning('Scheduled publish failed', ['post_id' => $post->id, 'error' => $result['error'] ?? 'unknown']);
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Scheduled publish error', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            }
        }

        return [
            'ok' => true,
            'published' => $published,
            'failed' => $failed,
            'total' => $posts->count(),
        ];
    }

    /**
     * Generate content ideas based on store products and trending topics.
     */
    public function generateIdeas(Store $store, int $count = 5): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();

        $llm = app(LlmClient::class);

        if (!$llm->available()) {
            return [
                'ok' => true,
                'ideas' => $this->templateIdeas($store, $count),
                'source' => 'template',
            ];
        }

        $system = <<<PROMPT
You are a content strategist for an Indian D2C e-commerce store. Generate blog post ideas that will:
1. Answer questions Indian shoppers ask AI assistants
2. Include the brand name naturally for AI citation
3. Cover trending topics in Indian e-commerce
4. Include pricing in ₹ and India-specific context

Return a JSON array of objects with: title, keyword, category (guide/comparison/product/category), reason (why this topic matters)
PROMPT;

        $user = "Brand: {$brand}\nDomain: https://{$domain}\n\nGenerate {$count} blog post ideas for this Indian D2C store.";

        $response = $llm->chat($system, $user, json: true);

        if ($response) {
            $ideas = json_decode(trim($response, "` \n"), true);
            if (is_array($ideas)) {
                return [
                    'ok' => true,
                    'ideas' => $ideas,
                    'source' => 'llm',
                ];
            }
        }

        return [
            'ok' => true,
            'ideas' => $this->templateIdeas($store, $count),
            'source' => 'template',
        ];
    }

    /**
     * Template-based content ideas when LLM is not available.
     */
    private function templateIdeas(Store $store, int $count): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));

        $ideas = [
            [
                'title' => "Best {$brand} Products for Indian Skin in 2026",
                'keyword' => "best {$brand} products",
                'category' => 'product',
                'reason' => 'Product roundups are highly cited by AI engines',
            ],
            [
                'title' => "{$brand} vs Competitors: Which is Better for Indian Consumers?",
                'keyword' => "{$brand} comparison India",
                'category' => 'comparison',
                'reason' => 'Comparison articles get cited when shoppers ask "which is better"',
            ],
            [
                'title' => "How to Choose the Right {$brand} Product for Your Needs",
                'keyword' => "how to choose {$brand}",
                'category' => 'guide',
                'reason' => 'How-to guides answer common AI shopping queries',
            ],
            [
                'title' => "{$brand} Price Guide: What Indian Shoppers Need to Know",
                'keyword' => "{$brand} price India",
                'category' => 'guide',
                'reason' => 'Price queries are among the most common AI shopping questions',
            ],
            [
                'title' => "Top Indian D2C Brands in 2026: {$brand} and More",
                'keyword' => "top D2C brands India 2026",
                'category' => 'category',
                'reason' => 'Category articles help AI engines understand your market position',
            ],
        ];

        return array_slice($ideas, 0, $count);
    }
}
