<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Post;
use App\Services\LlmClient;
use Illuminate\Http\Request;

class MarketingController extends Controller
{
    public function home()
    {
        return view('marketing.home');
    }

    public function pricing()
    {
        return view('marketing.pricing');
    }

    public function scorecard()
    {
        $demo = \App\Models\Store::where('is_demo', true)->first();
        $score = null;
        if ($demo) {
            $latest = $demo->audits()->where('status', 'completed')->latest()->first();
            $score = $latest ? $latest->summary : null;
        }
        return view('marketing.scorecard', [
            'demoScore' => $score,
            'llmConfigured' => app(LlmClient::class)->available(),
        ]);
    }

    public function captureLead(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'brand' => ['nullable', 'string', 'max:120'],
            'shop_url' => ['nullable', 'string', 'max:160'],
        ]);
        Lead::firstOrCreate(['email' => $data['email']], [
            'brand' => $data['brand'] ?? null,
            'shop_url' => $data['shop_url'] ?? null,
            'source' => $request->input('source', 'scorecard'),
        ]);
        return back()->with('status', 'Thanks! We will email your free AI Readiness Score within 24 hours.');
    }

    public function blog()
    {
        $posts = Post::whereNotNull('published_at')->orderByDesc('published_at')->get();
        return view('marketing.blog', ['posts' => $posts]);
    }

    public function blogShow(string $slug)
    {
        $post = Post::where('slug', $slug)->whereNotNull('published_at')->firstOrFail();
        return view('marketing.post', ['post' => $post]);
    }

    public function privacy()
    {
        return view('marketing.privacy');
    }

    public function terms()
    {
        return view('marketing.terms');
    }
}
