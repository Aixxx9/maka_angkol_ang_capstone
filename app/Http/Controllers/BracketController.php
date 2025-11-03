<?php

namespace App\Http\Controllers;

use App\Models\{Competition, Game, Sport, Team};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class BracketController extends Controller
{
    public function create()
    {
        return Inertia::render('Brackets/Builder', [
            'sports' => Sport::orderBy('name')->get(['id','name']),
            'teams' => Team::with(['school:id,name,logo_path', 'sport:id,name'])
                ->get(['id','name','school_id','sport_id']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sport_id' => 'required|exists:sports,id',
            'size' => 'required|in:4,8,16',
            'name' => 'nullable|string|max:255',
            'seed_team_ids' => 'required|array',
            'seed_team_ids.*' => 'integer|exists:teams,id',
        ]);

        $size = (int) $data['size'];
        $seedIds = array_values(array_unique(array_map('intval', $data['seed_team_ids'])));
        if (count($seedIds) !== $size) {
            return back()->withErrors(['seed_team_ids' => 'Please pick exactly '.$size.' teams.']);
        }

        // Validate all teams belong to the same sport
        $count = Team::whereIn('id', $seedIds)->where('sport_id', $data['sport_id'])->count();
        if ($count !== $size) {
            return back()->withErrors(['seed_team_ids' => 'All teams must belong to the selected sport.']);
        }

        $sport = Sport::findOrFail($data['sport_id']);
        $name = $data['name'] ?? ($sport->name.' Bracket');

        $comp = Competition::create([
            'name' => $name,
            'sport_id' => $sport->id,
            'bracket_type' => 'single_elimination',
            'status' => 'upcoming',
        ]);

        // Pair adjacent seeds: [0,1], [2,3], ...
        $pairs = array_chunk($seedIds, 2);
        $now = now()->addDay()->setTime(12, 0, 0);
        foreach ($pairs as $idx => $pair) {
            Game::create([
                'sport_id' => $sport->id,
                'competition_id' => $comp->id,
                'round' => 1,
                'bracket_pos' => $idx + 1,
                'home_team_id' => $pair[0],
                'away_team_id' => $pair[1],
                'starts_at' => $now->copy()->addMinutes($idx * 90),
                'venue' => null,
                'status' => 'scheduled',
            ]);
        }

        return redirect()->route('brackets.builder', $comp)->with('success', 'Bracket created.');
    }

    public function builder(Competition $competition)
    {
        $competition->load(['sport']);
        $games = Game::with(['homeTeam.school', 'awayTeam.school'])
            ->where('competition_id', $competition->id)
            ->orderBy('round')->orderBy('bracket_pos')
            ->get()
            ->groupBy('round')->values();

        return Inertia::render('Brackets/Builder', [
            'sports' => Sport::orderBy('name')->get(['id','name']),
            'teams' => Team::with(['school:id,name,logo_path', 'sport:id,name'])
                ->get(['id','name','school_id','sport_id']),
            'competition' => $competition,
            'rounds' => $games,
        ]);
    }
}

