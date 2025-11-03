<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use App\Models\Game;
use App\Models\Athlete;
use App\Models\Sport;
// use App\Models\Highlight;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Featured news: up to 4
        $featured = NewsPost::published()->featured()->orderByDesc('published_at')->take(4)->get();

        if ($featured->count() < 4) {
            $fill = NewsPost::published()
                ->whereNotIn('id', $featured->pluck('id'))
                ->orderByDesc('published_at')
                ->take(4 - $featured->count())
                ->get();
            $featured = $featured->concat($fill);
        }

        $featured = $featured->map(function ($p) {
            return [
                'id'        => $p->id,
                'title'     => $p->title,
                'slug'      => $p->slug,
                'excerpt'   => $p->excerpt ?: Str::limit(strip_tags($p->body), 140),
                'cover'     => $p->cover_url,
                'category'  => $p->category,
                'published' => optional($p->published_at)->toIso8601String(),
                'views'     => $p->views_count,
            ];
        });

        // News list: latest excluding featured
        $news = NewsPost::published()
            ->whereNotIn('id', $featured->pluck('id'))
            ->orderByDesc('published_at')
            ->take(9)
            ->get()
            ->map(function ($p) {
                return [
                    'id'        => $p->id,
                    'title'     => $p->title,
                    'slug'      => $p->slug,
                    'excerpt'   => $p->excerpt ?: Str::limit(strip_tags($p->body), 120),
                    'category'  => $p->category,
                    'published' => optional($p->published_at)->diffForHumans(),
                    'cover'     => $p->cover_url,
                ];
            });

        // Build per-sport groupings for players and games
        $sports = Sport::orderBy('name')->get();

        // Top 20 players of the month per sport (ranked by total points this month)
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $playersBySport = $sports->map(function ($sport) use ($startOfMonth, $endOfMonth) {
            $players = Athlete::with(['sport', 'team'])
                ->where('sport_id', $sport->id)
                ->withSum([
                    'gameStats as month_points' => function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->whereBetween('game_date', [$startOfMonth, $endOfMonth]);
                    },
                ], 'points')
                ->withSum([
                    'gameStats as month_rebounds' => function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->whereBetween('game_date', [$startOfMonth, $endOfMonth]);
                    },
                ], 'rebounds')
                ->withSum([
                    'gameStats as month_assists' => function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->whereBetween('game_date', [$startOfMonth, $endOfMonth]);
                    },
                ], 'assists')
                ->orderByDesc('month_points')
                ->take(20)
                ->get()
                ->map(function ($a) {
                    return [
                        'id'            => $a->id,
                        'name'          => $a->full_name,
                        'sport'         => optional($a->sport)->name,
                        'team'          => optional($a->team)->name,
                        'photo'         => $a->avatar_url,
                        'award'         => null,
                        'month_points'  => (int) ($a->month_points ?? 0),
                        'month_rebounds'=> (int) ($a->month_rebounds ?? 0),
                        'month_assists' => (int) ($a->month_assists ?? 0),
                    ];
                });

            return [
                'sport'   => [
                    'id'   => $sport->id,
                    'name' => $sport->name,
                    'slug' => $sport->slug,
                ],
                'players' => $players,
            ];
        });

        // Upcoming 5 games per sport
        $gamesBySport = $sports->map(function ($sport) {
            $games = Game::with(['sport', 'homeTeam.school', 'awayTeam.school', 'teams.school'])
                ->where('sport_id', $sport->id)
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->take(5)
                ->get()
                ->map(function ($g) {
                    $date = $g->starts_at ? Carbon::parse($g->starts_at)->toDayDateTimeString() : null;

                    // Build participating schools list (avoid duplicates)
                    $schools = collect();
                    if ($g->homeTeam && $g->homeTeam->school) {
                        $s = $g->homeTeam->school;
                        $schools->push([
                            'id'   => $s->id,
                            'name' => $s->name,
                            'logo' => $s->logo_path ? Storage::url($s->logo_path) : '/images/default-logo.png',
                        ]);
                    }
                    if ($g->awayTeam && $g->awayTeam->school) {
                        $s = $g->awayTeam->school;
                        $schools->push([
                            'id'   => $s->id,
                            'name' => $s->name,
                            'logo' => $s->logo_path ? Storage::url($s->logo_path) : '/images/default-logo.png',
                        ]);
                    }
                    foreach (($g->teams ?? []) as $t) {
                        if ($t->school) {
                            $s = $t->school;
                            $schools->push([
                                'id'   => $s->id,
                                'name' => $s->name,
                                'logo' => $s->logo_path ? Storage::url($s->logo_path) : '/images/default-logo.png',
                            ]);
                        }
                    }
                    $schools = $schools->unique('id')->values();

                    return [
                        'id'          => $g->id,
                        'sport'       => optional($g->sport)->name,
                        'date'        => $date,
                        'team_a'      => optional($g->homeTeam)->name,
                        'team_b'      => optional($g->awayTeam)->name,
                        'team_a_logo' => '/images/default-logo.png',
                        'team_b_logo' => '/images/default-logo.png',
                        'location'    => $g->venue,
                        'schools'     => $schools,
                    ];
                });

            return [
                'sport' => [
                    'id'   => $sport->id,
                    'name' => $sport->name,
                    'slug' => $sport->slug,
                ],
                'games' => $games,
            ];
        });

        return Inertia::render('Home', [
            'featured'       => $featured,
            'playersBySport' => $playersBySport,
            'gamesBySport'   => $gamesBySport,
            'news'           => $news,
        ]);
    }
}
