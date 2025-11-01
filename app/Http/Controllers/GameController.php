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
        $games = Game::with(['homeTeam.school', 'awayTeam.school', 'sport'])
            ->orderBy('sport_id')
            ->orderBy('starts_at', 'asc')
            ->get();

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
        ]);

        $data['status'] = 'scheduled';

        Game::create($data);

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
        ]);

        $game->update($data);

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
