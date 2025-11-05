<?php

namespace App\Http\Controllers;

use App\Models\{Game, GameEvent, Team, Sport};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class GameController extends Controller
{
    /**
     * PUBLIC: Schedule page
     * Shows all games sorted by sport and start time.
     */
    public function schedule()
    {
        // Show only upcoming 5 games per sport
        $sports = Sport::orderBy('name')->get();
        $games = collect();

        foreach ($sports as $sport) {
            $subset = Game::with(['homeTeam.school', 'awayTeam.school', 'sport', 'teams.school'])
                ->where('sport_id', $sport->id)
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at', 'asc')
                ->take(5)
                ->get();
            $games = $games->concat($subset);
        }

        return Inertia::render('Games/Schedule', [
            'games' => $games,
        ]);
    }

    /**
     * PUBLIC: Single game details page
     */
    public function show(Game $game)
    {
        $game->load(['homeTeam.school', 'awayTeam.school', 'sport', 'events', 'highlights']);

        return Inertia::render('Games/Show', [
            'game' => $game,
        ]);
    }

    /**
     * ADMIN (Public now): Create new schedule form
     */
    public function create()
    {
        $user = request()->user();
        $sportsQ = Sport::query()->select('id','name');
        $teamsQ = Team::with(['school','sport']);
        if ($user && $user->hasRole('mod')) {
            $allowed = $user->sports()->pluck('sports.id');
            $sportsQ->whereIn('id', $allowed);
            $teamsQ->whereIn('sport_id', $allowed);
        }
        return Inertia::render('Games/Create', [
            'teams'  => $teamsQ->get(),
            'sports' => $sportsQ->get(),
        ]);
    }

    /**
     * ADMIN (Public now): Store a new game schedule
     */
    public function store(Request $req)
    {
        $data = $req->validate([
            'sport_id'     => 'required|exists:sports,id',
            'home_team_id' => 'required|different:away_team_id|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id',
            'starts_at'    => 'required|date',
            'venue'        => 'nullable|string|max:255',
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:teams,id',
        ]);

        // Mods can only create schedules within their assigned sports
        $user = $req->user();
        if ($user && $user->hasRole('mod')) {
            $allowed = $user->sports()->where('sports.id', $data['sport_id'])->exists();
            abort_unless($allowed, 403);
            // Ensure chosen teams belong to the chosen sport
            foreach (['home_team_id','away_team_id'] as $k) {
                $ok = Team::where('id', $data[$k])->where('sport_id', $data['sport_id'])->exists();
                abort_unless($ok, 422, 'Team must match selected sport');
            }
            foreach (array_filter($req->input('participants', [])) as $pid) {
                $ok = Team::where('id', (int)$pid)->where('sport_id', $data['sport_id'])->exists();
                abort_unless($ok, 422, 'Participant team must match selected sport');
            }
        }

        $data['status'] = 'scheduled';

        $game = Game::create($data);

        // Attach participants: include home/away plus any additional unique team IDs
        $participantIds = array_filter($req->input('participants', []), fn($v) => !empty($v));
        $participantIds[] = (int) $data['home_team_id'];
        $participantIds[] = (int) $data['away_team_id'];
        $participantIds = array_values(array_unique(array_map('intval', $participantIds)));
        // Remove any accidental duplicates and ensure validity already validated above
        if (!empty($participantIds)) {
            // preserve order by array index via pivot 'position'
            $attach = [];
            foreach ($participantIds as $idx => $tid) {
                $attach[$tid] = ['position' => $idx];
            }
            $game->teams()->sync($attach);
        }

        return redirect()->route('schedule')
            ->with('success', 'Game schedule created successfully!');
    }

    /**
     * Add event to game (optional live tracking)
     */
    public function addEvent(Request $req, Game $game)
    {
        // Mods can only add events for games within their assigned sports
        $user = $req->user();
        if ($user && $user->hasRole('mod')) {
            $allowed = $user->sports()->where('sports.id', $game->sport_id)->exists();
            abort_unless($allowed, 403);
        }
        $payload = $req->validate([
            'team'  => 'required|in:home,away',
            'type'  => 'required|string',
            'value' => 'nullable|integer',
            'meta'  => 'nullable|array',
        ]);

        $event = $game->events()->create($payload);

        // Auto update score
        if (($payload['type'] ?? null) === 'score') {
            if ($payload['team'] === 'home') {
                $game->increment('home_score', $payload['value'] ?? 1);
            } else {
                $game->increment('away_score', $payload['value'] ?? 1);
            }
        }

        return response()->json([
            'ok' => true,
            'event' => $event,
            'game' => $game->fresh(),
        ]);
    }

    /**
     * Update status (scheduled/live/final)
     */
    public function setStatus(Request $req, Game $game)
    {
        $game->update($req->validate([
            'status' => 'required|in:scheduled,live,final',
        ]));

        return back();
    }

    /**
     * Update live embed stream link
     */
    public function setEmbed(Request $req, Game $game)
    {
        $game->update($req->validate([
            'live_embed_url' => 'nullable|url',
        ]));

        return back();
    }

    /* ------------------------------------------------------------------
     |  ✅ NEW: Public Edit / Update / Delete for Games
     |------------------------------------------------------------------*/

    /**
     * Show edit form for a game
     */
    public function edit(Game $game)
    {
        $game->load(['sport', 'homeTeam.school', 'awayTeam.school']);

        return Inertia::render('Games/Edit', [
            'game' => $game,
            'teams' => Team::with(['school', 'sport'])->get(),
            'sports' => Sport::select('id', 'name')->get(),
        ]);
    }

    /**
     * Update a game schedule
     */
    public function update(Request $req, Game $game)
    {
        $data = $req->validate([
            'sport_id'     => 'required|exists:sports,id',
            'home_team_id' => 'required|different:away_team_id|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id',
            'starts_at'    => 'required|date',
            'venue'        => 'nullable|string|max:255',
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:teams,id',
        ]);

        $game->update($data);

        // Sync participants including home/away
        $participantIds = array_filter($req->input('participants', []), fn($v) => !empty($v));
        $participantIds[] = (int) $data['home_team_id'];
        $participantIds[] = (int) $data['away_team_id'];
        $participantIds = array_values(array_unique(array_map('intval', $participantIds)));
        if (!empty($participantIds)) {
            $attach = [];
            foreach ($participantIds as $idx => $tid) {
                $attach[$tid] = ['position' => $idx];
            }
            $game->teams()->sync($attach);
        } else {
            // Ensure at least home/away are attached
            $game->teams()->sync([
                (int) $data['home_team_id'] => ['position' => 0],
                (int) $data['away_team_id'] => ['position' => 1],
            ]);
        }

        return redirect()->route('schedule')->with('success', 'Game updated successfully!');
    }

    /**
     * Delete a game schedule
     */
    public function destroy(Game $game)
    {
        $game->delete();
        return redirect()->route('schedule')->with('success', 'Game deleted successfully!');
    }

    /**
     * FINALIZE: set final scores and auto-advance bracket winners
     */
    public function finalize(Request $req, Game $game)
    {
        $data = $req->validate([
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
            'participant_scores' => 'nullable|array',
            'participant_scores.*' => 'integer|min:0',
        ]);

        $game->update(array_merge($data, ['status' => 'final']));

        // Save per-participant scores on the pivot (including home/away and any extra participants)
        $scores = $req->input('participant_scores', []);
        if (is_array($scores) && !empty($scores)) {
            foreach ($scores as $teamId => $score) {
                $tid = (int) $teamId; $val = max(0, (int) $score);
                try {
                    $game->teams()->updateExistingPivot($tid, ['score' => $val]);
                } catch (\Throwable $e) {
                    // Ignore if team is not attached to this game
                }
            }
        }

        // Bracket auto-advance (single-elimination)
        if ($game->competition_id && $game->round && $game->bracket_pos) {
            $compId = (int) $game->competition_id;
            $round = (int) $game->round;
            $pos = (int) $game->bracket_pos;

            // Find sibling game in the same round
            $siblingPos = $pos % 2 === 1 ? $pos + 1 : $pos - 1;
            $sibling = Game::where('competition_id', $compId)
                ->where('round', $round)
                ->where('bracket_pos', $siblingPos)
                ->first();

            // Only advance if sibling exists and is also final
            if ($sibling && $sibling->status === 'final') {
                $oddPos = min($pos, $siblingPos); // odd position in the pair
                $evenPos = max($pos, $siblingPos);

                $winnerTeamId = function (Game $g) {
                    if ($g->home_score > $g->away_score) return (int) $g->home_team_id;
                    if ($g->away_score > $g->home_score) return (int) $g->away_team_id;
                    return null; // tie ignored
                };

                $winnerOdd = $winnerTeamId($pos % 2 === 1 ? $game : $sibling);
                $winnerEven = $winnerTeamId($pos % 2 === 0 ? $game : $sibling);

                if ($winnerOdd && $winnerEven) {
                    $nextRound = $round + 1;
                    $nextPos = (int) ceil($oddPos / 2);

                    $next = Game::where('competition_id', $compId)
                        ->where('round', $nextRound)
                        ->where('bracket_pos', $nextPos)
                        ->first();

                    if (!$next) {
                        // Create next game when both winners are known
                        Game::create([
                            'sport_id' => $game->sport_id,
                            'competition_id' => $compId,
                            'round' => $nextRound,
                            'bracket_pos' => $nextPos,
                            'home_team_id' => $winnerOdd,    // odd -> home
                            'away_team_id' => $winnerEven,   // even -> away
                            'starts_at' => now()->addDay()->setTime(18, 0, 0),
                            'status' => 'scheduled',
                        ]);
                    }
                }
            }
        }

        return back()->with('success', 'Game finalized.');
    }
}
