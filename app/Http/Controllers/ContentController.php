<?php

namespace App\Http\Controllers;

use App\Models\ContentPost;
use App\Models\Store;
use App\Services\LlmClient;
use App\Services\SmartBlogger;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    private function store(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request)
    {
        $store = $this->store($request);
        $posts = $store->contentPosts()->orderByDesc('updated_at')->get()->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'keyword' => $p->keyword,
            'category' => $p->category,
            'tone' => $p->tone,
            'status' => $p->status,
            'word_count' => $p->word_count,
            'faqs' => $p->faqs,
            'body' => $p->body,
            'meta_title' => $p->meta_title,
            'meta_description' => $p->meta_description,
            'article_url' => $p->shopify_article_url,
            'updated_at' => $p->updated_at?->toIso8601String(),
        ]);
        return response()->json(['posts' => $posts]);
    }

    public function generate(Request $request)
    {
        $store = $this->store($request);
        $keyword = trim((string) $request->input('keyword'));
        if ($keyword === '') {
            return response()->json(['error' => 'Keyword is required'], 422);
        }
        $post = app(SmartBlogger::class)->generate(
            $store,
            $keyword,
            (string) $request->input('category', 'guide'),
            (string) $request->input('tone', 'informative'),
        );
        return response()->json(['post' => $post], 201);
    }

    public function regenerate(Request $request, int $id)
    {
        $store = $this->store($request);
        $post = $store->contentPosts()->findOrFail($id);
        $post->delete();
        $fresh = app(SmartBlogger::class)->generate($store, $post->keyword, $post->category, $post->tone);
        return response()->json(['post' => $fresh]);
    }

    public function publish(Request $request, int $id)
    {
        $store = $this->store($request);
        $post = $store->contentPosts()->findOrFail($id);
        $result = app(SmartBlogger::class)->publish($store, $post);
        if (! $result['ok']) {
            return response()->json($result, 422);
        }
        return response()->json($result);
    }

    public function destroy(Request $request, int $id)
    {
        $store = $this->store($request);
        $store->contentPosts()->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function sentiment(Request $request)
    {
        $store = $this->store($request);
        $llm = app(LlmClient::class);

        if (! $llm->available()) {
            return response()->json([
                'available' => false,
                'message' => 'AI Sentiment needs an LLM API key. Add OPENROUTER_API_KEY (free models available), OPENAI_API_KEY, or GEMINI_API_KEY in super admin → AI Settings.',
            ]);
        }

        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $queries = $store->queries()->where('active', true)->take(5)->get()->pluck('query')->implode(' | ');

        $text = $llm->chat(
            "You analyze how AI shopping answers describe a brand. Return STRICT JSON only: {\"sentiment\": \"positive|mixed|neutral|negative\", \"score\": 0-100, \"themes\": [\"...\"], \"quote\": \"one representative line\", \"advice\": \"one concrete recommendation\"}",
            "Brand: {$brand} (domain: {$store->hostname()}). Sample AI queries shoppers use: {$queries}. Summarize the sentiment AI answers currently express about this brand.",
            json: true,
        );

        if ($text === null) {
            return response()->json(['available' => false, 'message' => 'LLM call failed — try again shortly.']);
        }

        $data = json_decode(trim($text, "` \n"), true);
        if (! is_array($data)) {
            return response()->json(['available' => false, 'message' => 'Could not parse the model response.']);
        }

        return response()->json([
            'available' => true,
            'sentiment' => $data['sentiment'] ?? 'neutral',
            'score' => (int) ($data['score'] ?? 50),
            'themes' => $data['themes'] ?? [],
            'quote' => $data['quote'] ?? '',
            'advice' => $data['advice'] ?? '',
        ]);
    }
}
