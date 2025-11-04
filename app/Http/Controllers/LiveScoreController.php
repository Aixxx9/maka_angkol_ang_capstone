<?php

namespace App\Http\Controllers;

use App\Events\ScoreboardUpdated;
use App\Models\{Scoreboard, School, Sport};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LiveScoreController extends Controller
{
    /**
     * Admin: Manual live scoring screen
     */
    public function index()
    {
        $schools = School::orderBy('name')->get(['id','name','slug','logo_path']);
        $sports = Sport::orderBy('name')->get(['id','name','slug']);
        $active = Scoreboard::with(['leftSchool','rightSchool','sport'])
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();

        $payload = $active ? $this->format($active) : null;

        return Inertia::render('LiveScoring/Index', [
            'schools' => $schools,
            'sports' => $sports,
            'scoreboard' => $payload,
        ]);
    }

    /**
     * Start or update the active scoreboard (resets scores if changed pairing)
     */
    public function start(Request $request)
    {
        $data = $request->validate([
            'left_school_id' => 'required|exists:schools,id',
            'right_school_id' => 'required|different:left_school_id|exists:schools,id',
            'sport_id' => 'required|exists:sports,id',
            'match_label' => 'nullable|string|max:100',
        ]);

        $board = Scoreboard::where('is_active', true)->latest('updated_at')->first();

        if (!$board) {
            $board = Scoreboard::create(array_merge($data, [
                'left_score' => 0,
                'right_score' => 0,
                'is_active' => true,
            ]));
        } else {
            // If pairing or sport changed, reset scores
            $pairingChanged = ($board->left_school_id != $data['left_school_id'])
                || ($board->right_school_id != $data['right_school_id'])
                || ($board->sport_id != $data['sport_id']);
            $board->fill($data);
            if ($pairingChanged) {
                $board->left_score = 0;
                $board->right_score = 0;
            }
            $board->is_active = true;
            $board->save();
        }

        $board->load(['leftSchool','rightSchool','sport']);
        broadcast(new ScoreboardUpdated($board))->toOthers();

        return back()->with('success', 'Live scoreboard is active.');
    }

    /**
     * Increment/decrement a side.
     */
    public function score(Request $request)
    {
        $payload = $request->validate([
            'side' => 'required|in:left,right',
            'delta' => 'required|integer',
        ]);

        $board = Scoreboard::where('is_active', true)->latest('updated_at')->firstOrFail();

        if ($payload['side'] === 'left') {
            $board->left_score = max(0, (int)$board->left_score + (int)$payload['delta']);
        } else {
            $board->right_score = max(0, (int)$board->right_score + (int)$payload['delta']);
        }
        $board->save();

        $board->load(['leftSchool','rightSchool','sport']);
        broadcast(new ScoreboardUpdated($board))->toOthers();

        return response()->json(['ok' => true, 'scoreboard' => $this->format($board)]);
    }

    public function reset()
    {
        $board = Scoreboard::where('is_active', true)->latest('updated_at')->firstOrFail();
        $board->update(['left_score' => 0, 'right_score' => 0]);
        $board->load(['leftSchool','rightSchool','sport']);
        broadcast(new ScoreboardUpdated($board))->toOthers();
        return back();
    }

    public function hide()
    {
        // Toggle visibility of the latest scoreboard (active or not)
        $board = Scoreboard::with(['leftSchool','rightSchool','sport'])
            ->orderByDesc('updated_at')
            ->first();

        if (!$board) {
            return response()->json(['ok' => false, 'message' => 'No scoreboard'], 404);
        }

        $board->is_active = !$board->is_active;
        $board->save();

        broadcast(new ScoreboardUpdated($board))->toOthers();

        return response()->json([
            'ok' => true,
            'active' => (bool) $board->is_active,
            'scoreboard' => $this->format($board),
        ]);
    }

    /**
     * Public: current active scoreboard (for polling fallback)
     */
    public function current()
    {
        $board = Scoreboard::with(['leftSchool','rightSchool','sport'])
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();

        return response()->json($board ? $this->format($board) : null);
    }

    protected function format(Scoreboard $sb): array
    {
        return [
            'id' => $sb->id,
            'match_label' => $sb->match_label,
            'left_score' => (int) $sb->left_score,
            'right_score' => (int) $sb->right_score,
            'is_active' => (bool) $sb->is_active,
            'sport' => [
                'id' => $sb->sport_id,
                'name' => optional($sb->sport)->name,
            ],
            'left_school' => [
                'id' => $sb->left_school_id,
                'name' => optional($sb->leftSchool)->name,
                'logo' => $this->logo(optional($sb->leftSchool)->logo_path ?? null),
            ],
            'right_school' => [
                'id' => $sb->right_school_id,
                'name' => optional($sb->rightSchool)->name,
                'logo' => $this->logo(optional($sb->rightSchool)->logo_path ?? null),
            ],
        ];
    }

    protected function logo(?string $path): string
    {
        if (!$path) return '/images/default-logo.png';
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        return Storage::url($path);
    }
}
