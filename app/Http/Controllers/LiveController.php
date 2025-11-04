<?php
namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LiveController extends Controller
{
    public function show(Game $game)
    {
        $game->load(['homeTeam.school','awayTeam.school','events']);
        return Inertia::render('Live/Show', ['game' => $game]);
    }

    /**
     * Admin: Show Go Live form
     */
    public function create()
    {
        // Upcoming/scheduled + live games to choose from
        $games = Game::with(['homeTeam.school','awayTeam.school','sport'])
            ->orderBy('starts_at','asc')
            ->take(100)
            ->get()
            ->map(function ($g) {
                $home = optional($g->homeTeam->school)->name ?: optional($g->homeTeam)->name;
                $away = optional($g->awayTeam->school)->name ?: optional($g->awayTeam)->name;
                return [
                    'id' => $g->id,
                    'label' => trim(($home ?: 'TBD') . ' vs ' . ($away ?: 'TBD')),
                    'starts_at' => optional($g->starts_at)->toDateTimeString(),
                    'status' => $g->status,
                    'live_title' => $g->live_title,
                    'live_embed_url' => $g->live_embed_url,
                ];
            });

        $current = Game::where('status', 'live')
            ->whereNotNull('live_embed_url')
            ->latest('updated_at')
            ->first(['id','live_title','live_embed_url']);

        return Inertia::render('Live/Create', [
            'games' => $games,
            'current' => $current,
        ]);
    }

    /**
     * Admin: Start a live stream for a game
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'game_id' => 'required|exists:games,id',
            'title'   => 'required|string|max:255',
            'url'     => 'required|url',
        ]);

        $game = Game::findOrFail($data['game_id']);
        $embed = $this->normalizeEmbed($data['url']);

        // Optionally, stop other live games to ensure one-at-a-time
        Game::where('status', 'live')
            ->where('id', '!=', $game->id)
            ->update(['status' => 'scheduled']);

        $game->update([
            'live_title' => $data['title'],
            'live_embed_url' => $embed,
            'status' => 'live',
        ]);

        return redirect()->route('live.show', $game)->with('success', 'Live started.');
    }

    /**
     * Admin: Stop an active live stream
     */
    public function stop(Game $game)
    {
        $game->update([
            'status' => 'scheduled',
            // keep the URL for reuse; comment next line to clear instead
            // 'live_embed_url' => null,
        ]);

        return redirect()->route('home')->with('success', 'Live stopped.');
    }

    /**
     * Convert common video URLs to embeddable iframe URLs.
     */
    protected function normalizeEmbed(string $url): string
    {
        $u = trim($url);

        // If admin pasted full iframe HTML, extract the src directly
        if (stripos($u, '<iframe') !== false) {
            if (preg_match('~src\s*=\s*[\"\']([^\"\']+)~i', $u, $m)) {
                $u = $m[1];
            }
        }

        // Force https for better embed compatibility
        if (stripos($u, 'http://') === 0) {
            $u = 'https://' . substr($u, 7);
        }
        if (!preg_match('~^https?://~i', $u)) {
            $u = 'https://' . ltrim($u, '/');
        }

        // YouTube: youtu.be/ID or youtube.com/watch?v=ID
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/))([\w-]{6,})~i', $u, $m)) {
            $id = $m[1];
            return "https://www.youtube.com/embed/{$id}?autoplay=1&rel=0";
        }

        // Facebook video/live URL -> prefer proper plugin endpoints.
        if (stripos($u, 'facebook.com') !== false) {
            // Normalize to www.facebook.com
            $u = preg_replace('~^https?://(m|web)\.facebook\.com~i', 'https://www.facebook.com', $u);
            $enc = urlencode($u);
            // Specific video/watch links -> video plugin
            if (preg_match('~facebook\.com/(?:watch/\?v=|.*?/videos/|video\.php\?v=)~i', $u)) {
                return "https://www.facebook.com/plugins/video.php?href={$enc}&show_text=0&autoplay=1";
            }
            // Otherwise, treat it as a Page live channel
            // If this is not clearly a page URL, fall back to generic post embed
            if (preg_match('~facebook\.com/[^/]+/?$~i', $u) || preg_match('~facebook\.com/.+/live~i', $u)) {
                return "https://www.facebook.com/plugins/live_video.php?href={$enc}&show_text=0&autoplay=1";
            }
            return "https://www.facebook.com/plugins/post.php?href={$enc}&show_text=true";
        }

        // Vimeo: vimeo.com/ID
        if (preg_match('~vimeo\.com/(\d+)~i', $u, $m)) {
            $id = $m[1];
            return "https://player.vimeo.com/video/{$id}?autoplay=1";
        }

        // Fallback: assume it is already an embeddable URL
        return $u;
    }
}
