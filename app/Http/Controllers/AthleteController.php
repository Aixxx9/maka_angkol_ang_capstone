<?php

namespace App\Http\Controllers;

use App\Models\Athlete;
use App\Models\Sport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AthleteController extends Controller
{
    public function index(Request $request)
    {
        $sportSlug = $request->query('sport');
        $query = Athlete::query()->with('sport', 'team');

        if ($sportSlug) {
            $query->whereHas('sport', fn($q) => $q->where('slug', $sportSlug));
        }

        $players = $query->get();
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
}
