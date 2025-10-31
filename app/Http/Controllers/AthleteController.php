<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Athlete;
use App\Models\Sport;

class AthleteController extends Controller
{
    public function index(Request $request)
    {
        $sportSlug = $request->query('sport');

        // ✅ Fetch sports dynamically from DB
        $sports = Sport::select('id', 'name', 'slug', 'icon_path')->orderBy('name')->get();

        // ✅ If a sport is selected, load athletes for that sport
        $players = [];
        if ($sportSlug) {
            $players = Athlete::where('sport_slug', $sportSlug)
                ->latest()
                ->get();
        }

        return Inertia::render('Athletes/Index', [
            'sports'  => $sports,
            'sport'   => $sportSlug,
            'players' => $players,
            'query'   => $request->query('q', ''),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'number'     => 'nullable|string|max:10',
            'position'   => 'nullable|string|max:100',
            'team_id'    => 'nullable|integer',
            'sport_slug' => 'required|string|exists:sports,slug',
            'avatar'     => 'nullable|image|max:2048',
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
            'sport_slug' => $validated['sport_slug'],
            'avatar_path'=> $avatarPath,
        ]);

        return redirect()->back()->with('success', 'Athlete added successfully!');
    }
}
