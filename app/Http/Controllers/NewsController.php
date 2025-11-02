<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function home()
    {
        $hero = NewsPost::published()
            ->featured()
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        if ($hero->count() < 3) {
            $fill = NewsPost::published()
                ->whereNotIn('id', $hero->pluck('id'))
                ->orderByDesc('published_at')
                ->take(3 - $hero->count())
                ->get();
            $hero = $hero->concat($fill);
        }

        $hero = $hero->map(fn($p) => [
            'id'        => $p->id,
            'title'     => $p->title,
            'slug'      => $p->slug,
            'excerpt'   => $p->excerpt ?: Str::limit(strip_tags($p->body), 140),
            'cover'     => $p->cover_url,
            'category'  => $p->category,
            'published' => optional($p->published_at)->toIso8601String(),
            'views'     => $p->views_count,
        ]);

        $latest = NewsPost::published()
            ->whereNotIn('id', $hero->pluck('id'))
            ->orderByDesc('published_at')
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'title'     => $p->title,
                'slug'      => $p->slug,
                'excerpt'   => $p->excerpt ?: Str::limit(strip_tags($p->body), 120),
                'category'  => $p->category,
                'published' => optional($p->published_at)->diffForHumans(),
                'views'     => $p->views_count,
                'cover'     => $p->cover_url,
            ]);

        return Inertia::render('News/Index', [
            'hero'   => $hero,
            'latest' => $latest,
        ]);
    }

    public function index()
    {
        return $this->home();
    }

    public function show(NewsPost $news)
    {
        $news->increment('views_count');

        return Inertia::render('News/Show', [
            'post' => [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'excerpt' => $news->excerpt,
                'body' => $news->body,
                'category' => $news->category,
                'cover' => $news->cover_url,
                'views' => $news->views_count,
                'published' => optional($news->published_at)->toFormattedDateString(),
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('News/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'cover'       => 'nullable|image|max:80920',
            'category'    => 'nullable|string|max:50',
            'is_featured' => 'boolean',
            'published_at'=> 'nullable|date',
        ]);

        $coverPath = isset($data['cover'])
            ? $request->file('cover')->store('covers', 'public')
            : null;

        $post = NewsPost::create([
            'author_id'        => auth()->id(),
            'title'            => $data['title'],
            'slug'             => Str::slug($data['title']).'-'.time(),
            'excerpt'          => Str::limit(strip_tags($data['body']), 160),
            'body'             => $data['body'],
            'cover_image_path' => $coverPath,
            'category'         => $data['category'] ?? null,
            'is_featured'      => (bool)($data['is_featured'] ?? false),
            'published_at'     => ($data['published_at'] ?? null) ?: now(),
        ]);

        return redirect()->route('news.show', $post);
    }

    public function edit(NewsPost $news)
    {
        return Inertia::render('News/Edit', [
            'post' => [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'excerpt' => $news->excerpt,
                'body' => $news->body,
                'category' => $news->category,
                'cover' => $news->cover_url,
                'is_featured' => (bool)$news->is_featured,
                'published_at' => optional($news->published_at)?->format('Y-m-d\TH:i'),
            ],
        ]);
    }

    public function update(Request $request, NewsPost $news)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'cover'       => 'nullable|image|max:80920',
            'category'    => 'nullable|string|max:50',
            'is_featured' => 'boolean',
            'published_at'=> 'nullable|date',
        ]);

        $coverPath = $news->cover_image_path;
        if ($request->hasFile('cover')) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }
            $coverPath = $request->file('cover')->store('covers', 'public');
        }

        $news->update([
            'title'            => $data['title'],
            // Keep slug stable to avoid broken links
            'excerpt'          => Str::limit(strip_tags($data['body']), 160),
            'body'             => $data['body'],
            'cover_image_path' => $coverPath,
            'category'         => $data['category'] ?? null,
            'is_featured'      => (bool)($data['is_featured'] ?? false),
            'published_at'     => $data['published_at'] ?? $news->published_at,
        ]);

        return redirect()->route('news.show', $news);
    }

    public function destroy(NewsPost $news)
    {
        if ($news->cover_image_path) {
            Storage::disk('public')->delete($news->cover_image_path);
        }
        $news->delete();

        return redirect()->route('news.index');
    }
}
