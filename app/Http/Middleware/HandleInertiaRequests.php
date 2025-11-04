<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\School;
use App\Models\Game;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [

            // ✅ Auth user (already in your original code)
            'auth' => [
                'user' => fn() => $request->user() ? [
                    'id'    => $request->user()->id,
                    'name'  => $request->user()->name,
                    'roles' => $request->user()->getRoleNames(),
                ] : null,
            ],

            // ✅ NEW: Global list of schools for your header strip
            'schools' => fn() => School::select('id', 'name', 'slug', 'logo_path')->get(),

            // ✅ Global live banner/overlay (if any). Returns null when no active live.
            'live' => function () {
                $g = Game::where('status', 'live')->whereNotNull('live_embed_url')->latest('updated_at')->first();
                if (!$g) return null;
                return [
                    'game_id' => $g->id,
                    'title'   => $g->live_title ?: 'Live Now',
                    'embed'   => $g->live_embed_url,
                    'url'     => route('live.show', ['game' => $g->id]),
                ];
            },
        ]);
    }
}
