<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function home()
    {
        // 1) Pick up to 3 featured posts for the hero rail
        $hero = NewsPost::published()
            ->featured()
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        // If fewer than 3 featured, fill with latest (excluding already picked)
        if ($hero->count() < 3) {
            $fill = NewsPost::published()
                ->whereNotIn('id', $hero->pluck('id'))
                ->orderByDesc('published_at')
                ->take(3 - $hero->count())
                ->get();
            $hero = $hero->concat($fill);
        }

        // Shape hero for the front-end (only needed fields)
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

        // 2) Latest list (exclude hero ids)
        $latest = NewsPost::published()
            ->whereNotIn('id', $hero->pluck('id'))
            ->orderByDesc('published_at')
            ->take(10)
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'title'     => $p->title,
                'slug'      => $p->slug,
                'excerpt'   => $p->excerpt ?: Str::limit(strip_tags($p->body), 120),
                'category'  => $p->category,
                'published' => optional($p->published_at)->diffForHumans(),
                'views'     => $p->views_count,
            ]);

        return Inertia::render('Home', [
            'hero'   => $hero,
            'latest' => $latest,
        ]);
    }

    /* Optional: bump views when a post is read */
    public function show(NewsPost $news)
    {
        $news->increment('views_count');
        return Inertia::render('News/Show', ['post' => $news->only([
            'id','title','slug','excerpt','body','cover_image_path','category','views_count','published_at'
        ])]);
    }

    /* Admin create/store (make sure route is in /admin) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'cover'       => 'nullable|image|max:8192',
            'category'    => 'nullable|string|max:50',
            'is_featured' => 'boolean',
            'published_at'=> 'nullable|date',
        ]);

        $cover = isset($data['cover']) ? $request->file('cover')->store('covers','public') : null;

        $post = NewsPost::create([
            'author_id'        => auth()->id(),
            'title'            => $data['title'],
            'slug'             => Str::slug($data['title']).'-'.time(),
            'excerpt'          => Str::limit(strip_tags($data['body']), 160),
            'body'             => $data['body'],
            'cover_image_path' => $cover,
            'category'         => $data['category'] ?? null,
            'is_featured'      => (bool)($data['is_featured'] ?? false),
            'published_at'     => $data['published_at'] ?? now(),
        ]);

        return redirect()->route('news.show', $post);
    }
}
