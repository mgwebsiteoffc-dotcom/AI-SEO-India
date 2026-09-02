<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * SaaS-owner blog manager (/admin/blog): full editor for the marketing blog
 * with SEO/AEO fields (slug, meta title/keywords/description, category, FAQs
 * for FAQPage JSON-LD) plus category taxonomy management.
 */
class AdminBlogController extends Controller
{
    // ------------------------------------------------------------------ posts

    public function index(Request $request)
    {
        $posts = Post::query()
            ->with('category')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('body', 'like', '%'.$request->string('q').'%')
                ->orWhere('meta_keywords', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', (int) $request->query('category')))
            ->when($request->query('status') === 'draft', fn ($q) => $q->whereNull('published_at'))
            ->when($request->query('status') === 'published', fn ($q) => $q->whereNotNull('published_at'))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.blog', [
            'posts' => $posts,
            'categories' => BlogCategory::orderBy('name')->get(),
            'q' => (string) $request->query('q', ''),
            'filterCategory' => (int) $request->query('category', 0),
            'filterStatus' => (string) $request->query('status', ''),
        ]);
    }

    public function create()
    {
        return view('admin.blog-edit', [
            'post' => new Post(['author' => 'AI Visibility Team']),
            'categories' => BlogCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $data['slug'] = Post::uniqueSlug($data['slug'] ?: $data['title']);
        Post::create($data);

        return redirect()->route('admin.blog')->with('status', 'Article published.'.($data['published_at'] ? '' : ' (draft saved)'));
    }

    public function edit(Post $post)
    {
        return view('admin.blog-edit', [
            'post' => $post,
            'categories' => BlogCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request, $post->id);
        $data['slug'] = Post::uniqueSlug($data['slug'] ?: $data['title'], $post->id);
        $post->update($data);

        return redirect()->route('admin.blog')->with('status', 'Article updated.');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return back()->with('status', 'Article deleted.');
    }

    private function validated(Request $request, ?int $ignoreId): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($ignoreId)],
            'category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string', 'max:800'],
            'body' => ['required', 'string'],
            'author' => ['nullable', 'string', 'max:120'],
            'faqs' => ['nullable', 'array', 'max:12'],
            'faqs.*.q' => ['required_with:faqs', 'string', 'max:300'],
            'faqs.*.a' => ['required_with:faqs', 'string', 'max:2000'],
            'publish' => ['nullable', 'boolean'],
            'published_date' => ['nullable', 'date'],
        ]);

        // Normalise FAQ rows (drop blank entries, keep stable order).
        $faqs = [];
        foreach ($data['faqs'] ?? [] as $f) {
            $q = trim((string) ($f['q'] ?? ''));
            $a = trim((string) ($f['a'] ?? ''));
            if ($q !== '' && $a !== '') {
                $faqs[] = ['q' => $q, 'a' => $a];
            }
        }

        // Published semantics: publish = now (or an explicit back-date);
        // editing an already-published post without touching the date keeps
        // its original timestamp; unchecking moves it back to draft.
        $publishedAt = null;
        if ($request->boolean('publish')) {
            $explicit = $request->input('published_date');
            if ($explicit && strtotime((string) $explicit)) {
                $publishedAt = \Carbon\Carbon::parse($explicit)->startOfDay();
            } elseif ($ignoreId) {
                $publishedAt = Post::whereKey($ignoreId)->value('published_at') ?? now();
            } else {
                $publishedAt = now();
            }
        }
        unset($data['faqs'], $data['publish'], $data['published_date']);
        $data['faqs'] = $faqs ?: null;
        $data['published_at'] = $publishedAt;
        $data['slug'] = trim((string) ($data['slug'] ?? ''));
        $data['meta_keywords'] = trim((string) ($data['meta_keywords'] ?? '')) ?: null;
        $data['author'] = trim((string) ($data['author'] ?? '')) ?: 'AI Visibility Team';

        return $data;
    }

    // -------------------------------------------------------------- categories

    public function categories()
    {
        $categories = BlogCategory::withCount('posts')->orderBy('name')->get();

        return view('admin.blog-categories', ['categories' => $categories]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ]);
        BlogCategory::create([
            'name' => $data['name'],
            'slug' => BlogCategory::uniqueSlug($data['slug'] ?: $data['name']),
            'meta_description' => trim((string) ($data['meta_description'] ?? '')) ?: null,
        ]);

        return back()->with('status', 'Category added.');
    }

    public function deleteCategory(Request $request, BlogCategory $category)
    {
        // Articles keep their SEO value: moving them to "uncategorised".
        Post::where('category_id', $category->id)->update(['category_id' => null]);
        $category->delete();

        return back()->with('status', 'Category deleted (posts kept, uncategorised).');
    }
}
