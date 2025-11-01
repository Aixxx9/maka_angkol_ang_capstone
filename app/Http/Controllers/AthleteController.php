<?php

namespace App\Http\Controllers;

use App\Models\Athlete;
use App\Models\Sport;
use App\Models\PlayerGameStat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AthleteController extends Controller
{
    public function index(Request $request)
    {
        $sportSlug = $request->query('sport');

        $query = Athlete::query()
            ->with(['sport', 'team', 'school', 'gameStats'])
            ->withAvg('gameStats as ppg', 'points')
            ->withAvg('gameStats as rpg', 'rebounds')
            ->withAvg('gameStats as apg', 'assists')
            ->withAvg('gameStats as fg', 'fg_percent');

        if ($sportSlug) {
            $query->whereHas('sport', fn($q) => $q->where('slug', $sportSlug));
        }

        $players = $query->get()->map(function ($p) {
            // Normalize fields the front-end expects
            $p->team_name = optional($p->team)->name;
            $p->school_name = optional($p->school)->name;
            // Cast avgs to numbers with 1 decimal precision for consistency on the client
            foreach (['ppg', 'rpg', 'apg', 'fg'] as $k) {
                if (!is_null($p->{$k})) {
                    $p->{$k} = round((float) $p->{$k}, 1);
                }
            }
            return $p;
        });

        $sports = Sport::select('id', 'name', 'slug', 'icon_path')->get()->map(function ($s) {
            $s->icon_path = $s->icon_path ? Storage::url($s->icon_path) : '/images/default-sport.png';
            return $s;
        });

        return Inertia::render('Athletes/Index', [
            'players' => $players,
            'sports'  => $sports,
            'sport'   => $sportSlug,
        ]);
    }

    public function create()
{
    return inertia('Athletes/Create', [
        'sports' => \App\Models\Sport::select('id', 'name')->get(),
        // Use a different key to avoid shadowing shared 'schools' used in AppLayout
        'schoolOptions' => \App\Models\School::select('id', 'name')->get(),
    ]);
}


    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'number'     => 'nullable|string|max:10',
            'position'   => 'nullable|string|max:50',
            // If provided, ensure the team exists to satisfy FK
            'team_id'    => 'nullable|integer|exists:teams,id',
            // Use sport_id from the form instead of requiring a slug
            'sport_id'   => 'required|integer|exists:sports,id',
            'school_id'  => 'required|integer|exists:schools,id',
            'avatar'     => 'nullable|image|max:20480',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('athletes', 'public');
        }

        Athlete::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'number'     => $validated['number'],
            'position'   => $validated['position'],
            'team_id'    => $validated['team_id'],
            'sport_id'   => $validated['sport_id'],
            'school_id'  => $validated['school_id'],
            'avatar_path'=> $avatarPath,
        ]);

        return redirect()->route('athletes.index')->with('success', 'Player added successfully!');
    }

    public function update(Request $request, Athlete $athlete)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name'  => 'required|string|max:255',
        'number'     => 'nullable|string|max:10',
        'position'   => 'nullable|string|max:50',
        'team_id'    => 'nullable|integer',
        'avatar'     => 'nullable|image|max:10240', // 10MB
    ]);

    if ($request->hasFile('avatar')) {
        $path = $request->file('avatar')->store('avatars', 'public');
        $validated['avatar_path'] = $path;
    }

    $athlete->update($validated);
    return back()->with('success', 'Player updated successfully!');
}

public function show(Athlete $athlete)
{
    $athlete->load('gameStats'); // relationship
    return inertia('Athletes/Show', [
        'athlete' => $athlete,
    ]);
}

public function storeGameStat(Request $request, Athlete $athlete)
{
    $data = $request->validate([
        'points' => 'required|integer|min:0',
        'rebounds' => 'required|integer|min:0',
        'assists' => 'required|integer|min:0',
        'fg_percent' => 'required|numeric|min:0|max:100',
        'game_date' => 'required|date',
    ]);
    $athlete->gameStats()->create($data);
    return back()->with('success', 'Game record added!');
}

public function destroyGameStat(Athlete $athlete, $stat)
{
    $record = PlayerGameStat::where('athlete_id', $athlete->id)->where('id', $stat)->firstOrFail();
    $record->delete();
    return back()->with('success', 'Recent game record cleared.');
}

}
