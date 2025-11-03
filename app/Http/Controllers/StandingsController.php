<?php

namespace App\Http\Controllers;

use App\Models\{Game, Sport, Team, School};
use Illuminate\Http\Request;
use Inertia\Inertia;

class StandingsController extends Controller
{
    public function index(Request $request)
    {
        $sports = Sport::orderBy('name')->get(['id','name']);
        $sportId = $request->integer('sport_id');

        if ($sportId) {
            $standings = $this->computeStandings((int) $sportId);
            return Inertia::render('Standings/Index', [
                'sports' => $sports,
                'sportId' => (int) $sportId,
                'standings' => $standings,
            ]);
        }

        // Default: pick first sport if exists
        $selected = optional($sports->first())->id;
        $standings = $selected ? $this->computeStandings((int) $selected) : [];

        return Inertia::render('Standings/Index', [
            'sports' => $sports,
            'sportId' => $selected,
            'standings' => $standings,
        ]);
    }

    protected function computeStandings(int $sportId): array
    {
        // Collect all schools that have a team in this sport
        $teams = \App\Models\Team::with('school:id,name,logo_path')
            ->where('sport_id', $sportId)
            ->get(['id','school_id','sport_id']);

        $allSchoolIds = $teams->pluck('school_id')->unique()->filter()->values();
        $schools = School::whereIn('id', $allSchoolIds)->get(['id','name','logo_path'])->keyBy('id');

        // Initialize table with zeros for all schools
        $table = [];
        foreach ($allSchoolIds as $sid) {
            $s = $schools->get($sid);
            if ($s) {
                $table[$sid] = [
                    'school_id' => (int) $s->id,
                    'name' => $s->name,
                    'logo_path' => $s->logo_path,
                    'wins' => 0,
                    'losses' => 0,
                    'gp' => 0,
                ];
            }
        }

        // Apply results from finalized games
        $games = Game::with(['homeTeam.school', 'awayTeam.school'])
            ->where('sport_id', $sportId)
            ->where('status', 'final')
            ->get();

        foreach ($games as $g) {
            $homeTeam = $g->homeTeam; $awayTeam = $g->awayTeam;
            if (!$homeTeam || !$awayTeam) continue;
            $homeId = (int) $homeTeam->school_id; $awayId = (int) $awayTeam->school_id;

            if (!isset($table[$homeId]) && isset($schools[$homeId])) {
                $table[$homeId] = [
                    'school_id' => $homeId,
                    'name' => $schools[$homeId]->name,
                    'logo_path' => $schools[$homeId]->logo_path,
                    'wins' => 0,
                    'losses' => 0,
                    'gp' => 0,
                ];
            }
            if (!isset($table[$awayId]) && isset($schools[$awayId])) {
                $table[$awayId] = [
                    'school_id' => $awayId,
                    'name' => $schools[$awayId]->name,
                    'logo_path' => $schools[$awayId]->logo_path,
                    'wins' => 0,
                    'losses' => 0,
                    'gp' => 0,
                ];
            }

            if ($g->home_score === $g->away_score) {
                // ignore ties for now
                continue;
            }

            $winnerIsHome = $g->home_score > $g->away_score;
            $table[$homeId]['gp']++;
            $table[$awayId]['gp']++;

            if ($winnerIsHome) {
                $table[$homeId]['wins']++;
                $table[$awayId]['losses']++;
            } else {
                $table[$awayId]['wins']++;
                $table[$homeId]['losses']++;
            }
        }

        // Format to array with win% and sort
        $rows = array_values(array_map(function ($row) {
            $gp = (int) $row['gp'];
            $row['win_pct'] = $gp > 0 ? round(($row['wins'] / $gp) * 100, 1) : 0.0;
            return $row;
        }, $table));

        usort($rows, function ($a, $b) {
            // Sort by wins desc, win% desc, gp desc, name asc
            return [$b['wins'], $b['win_pct'], $b['gp'], $a['name']] <=> [$a['wins'], $a['win_pct'], $a['gp'], $b['name']];
        });

        return $rows;
    }
}
