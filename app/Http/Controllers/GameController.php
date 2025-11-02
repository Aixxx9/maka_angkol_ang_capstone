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
        return Inertia::render('Games/Create', [
            'teams'  => Team::with(['school', 'sport'])->get(),
            'sports' => Sport::select('id', 'name')->get(),
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
}
