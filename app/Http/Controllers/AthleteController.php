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

        // Only show approved athletes publicly
        $query->where('status', 'approved');

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

        $status = 'approved';
        // Mods submit pending entries subject to approval
        if ($request->user() && $request->user()->hasRole('mod')) {
            // Mod must be assigned to the sport
            $allowed = $request->user()->sports()->where('sports.id', $validated['sport_id'])->exists();
            abort_unless($allowed, 403);
            $status = 'pending';
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
            'status'     => $status,
            'submitted_by' => $request->user()?->id,
        ]);

        // Notify admins of pending submission
        if ($status === 'pending') {
            try {
                \App\Models\User::role(['admin','super-admin'])->each(function($admin){
                    $admin->notify(new \App\Notifications\PendingAthleteSubmitted());
                });
            } catch (\Throwable $e) {}
            return redirect()->route('athletes.index')->with('success', 'Submitted for approval.');
        }

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
        // Mods can only add stats for athletes in their assigned sports
        $user = $request->user();
        if ($user && $user->hasRole('mod')) {
            $allowed = $user->sports()->where('sports.id', $athlete->sport_id)->exists();
            abort_unless($allowed, 403);
        }
        // Accept dynamic stats via sport config, but keep backwards compatibility
        $baseValidated = $request->validate([
            'game_date' => 'required|date',
        ]);

        $sport = $athlete->sport;
        $config = collect($sport->stat_fields ?? [
            ['key' => 'points', 'label' => 'Points', 'type' => 'number', 'agg' => 'sum'],
            ['key' => 'rebounds', 'label' => 'Rebounds', 'type' => 'number', 'agg' => 'sum'],
            ['key' => 'assists', 'label' => 'Assists', 'type' => 'number', 'agg' => 'sum'],
            ['key' => 'fg_percent', 'label' => 'FG %', 'type' => 'percent', 'agg' => 'avg'],
        ]);

        $metrics = [];
        if (is_array($request->input('stats'))) {
            $stats = $request->input('stats', []);
            // Filter to configured keys and validate
            foreach ($config as $field) {
                $k = $field['key'] ?? null;
                if (!$k || !array_key_exists($k, $stats)) continue;
                $val = $stats[$k];
                if ($val === '' || $val === null) continue;
                if (!is_numeric($val)) {
                    return back()->withErrors(["stats.$k" => 'Must be numeric']);
                }
                $val = (float) $val;
                if (($field['type'] ?? 'number') === 'percent' && ($val < 0 || $val > 100)) {
                    return back()->withErrors(["stats.$k" => 'Percent must be between 0 and 100']);
                }
                $metrics[$k] = $val;
            }
        }

        // Backwards compatibility: accept legacy fields if provided
        $legacy = $request->only(['points', 'rebounds', 'assists', 'fg_percent']);
        // If no dynamic stats were posted and none of legacy provided, enforce legacy validation
        if (empty($metrics) && empty(array_filter($legacy, fn($v) => $v !== null && $v !== ''))) {
            $legacy = $request->validate([
                'points' => 'required|integer|min:0',
                'rebounds' => 'required|integer|min:0',
                'assists' => 'required|integer|min:0',
                'fg_percent' => 'required|numeric|min:0|max:100',
            ]);
        }

        $payload = array_merge(
            [
                'game_date' => $baseValidated['game_date'],
                'metrics' => !empty($metrics) ? $metrics : null,
            ],
            // Prefer dynamic values for known legacy columns if present
            [
                'points' => isset($metrics['points']) ? (int) $metrics['points'] : ($legacy['points'] ?? 0),
                'rebounds' => isset($metrics['rebounds']) ? (int) $metrics['rebounds'] : ($legacy['rebounds'] ?? 0),
                'assists' => isset($metrics['assists']) ? (int) $metrics['assists'] : ($legacy['assists'] ?? 0),
                // Avoid NULL in non-nullable column; default to 0 if not provided
                'fg_percent' => isset($metrics['fg_percent']) ? (float) $metrics['fg_percent'] : ($legacy['fg_percent'] ?? 0),
            ]
        );

        $athlete->gameStats()->create($payload);
        return back()->with('success', 'Game record added!');
    }

public function destroyGameStat(Athlete $athlete, $stat)
{
    // Mods can only delete stats for athletes in their assigned sports
    $user = request()->user();
    if ($user && $user->hasRole('mod')) {
        $allowed = $user->sports()->where('sports.id', $athlete->sport_id)->exists();
        abort_unless($allowed, 403);
    }
    $record = PlayerGameStat::where('athlete_id', $athlete->id)->where('id', $stat)->firstOrFail();
    $record->delete();
    return back()->with('success', 'Recent game record cleared.');
}

}
