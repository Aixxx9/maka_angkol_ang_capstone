<?php

namespace App\Http\Controllers;

use App\Models\{Game, Sport, School, Team};
use Illuminate\Http\Request;
use Inertia\Inertia;

class MatchesController extends Controller
{
    public function index(Request $request)
    {
        $sports = Sport::orderBy('name')->get(['id','name']);
        $schools = School::orderBy('name')->get(['id','name','slug','logo_path']);

        $q = Game::with(['sport','homeTeam.school','awayTeam.school','teams.school'])
            ->orderBy('starts_at', 'desc');

        if ($request->filled('sport_id')) {
            $q->where('sport_id', (int) $request->integer('sport_id'));
        }

        if ($request->filled('school_id')) {
            $schoolId = (int) $request->integer('school_id');
            $q->where(function ($w) use ($schoolId) {
                $w->whereHas('homeTeam', fn($h) => $h->where('school_id', $schoolId))
                  ->orWhereHas('awayTeam', fn($a) => $a->where('school_id', $schoolId))
                  ->orWhereHas('teams', fn($t) => $t->where('school_id', $schoolId));
            });
        }

        $all = $q->get();

        // Filter by winner school only among finished games
        if ($request->filled('winner_school_id')) {
            $winSchoolId = (int) $request->integer('winner_school_id');
            $all = $all->filter(function ($g) use ($winSchoolId) {
                if ($g->status !== 'final') return false;
                $winnerTeamId = null;
                if ($g->home_score > $g->away_score) $winnerTeamId = $g->home_team_id;
                if ($g->away_score > $g->home_score) $winnerTeamId = $g->away_team_id;
                if (!$winnerTeamId) return false; // tie not counted
                $team = ($winnerTeamId === $g->home_team_id) ? $g->homeTeam : $g->awayTeam;
                return $team && (int) $team->school_id === $winSchoolId;
            })->values();
        }

        $upcoming = $all->filter(fn($g) => $g->status !== 'final')
            ->sortBy('starts_at')->values();
        $finished = $all->filter(fn($g) => $g->status === 'final')
            ->sortByDesc('starts_at')->values();

        // Determine active sport for Standings / Bracket sections
        $activeSportId = $request->filled('sport_id')
            ? (int) $request->integer('sport_id')
            : optional($sports->first())->id;

        // Build standings for the active sport (include all schools with zeroes)
        $standings = [];
        if ($activeSportId) {
            $teams = Team::with('school:id,name,logo_path')
                ->where('sport_id', $activeSportId)
                ->get(['id','school_id','sport_id']);

            $allSchoolIds = $teams->pluck('school_id')->unique()->filter()->values();
            $schoolsMap = $allSchoolIds->isNotEmpty()
                ? School::whereIn('id', $allSchoolIds)->get(['id','name','logo_path'])->keyBy('id')
                : collect();

            $table = [];
            foreach ($allSchoolIds as $sid) {
                $s = $schoolsMap->get($sid);
                if ($s) {
                    $table[$sid] = [
                        'school_id' => (int) $s->id,
                        'name' => $s->name,
                        'logo_path' => $s->logo_path,
                        'wins' => 0,
                        'losses' => 0,
                        'gp' => 0,
                        'win_pct' => 0.0,
                    ];
                }
            }

            $finalGames = Game::with(['homeTeam.school', 'awayTeam.school'])
                ->where('sport_id', $activeSportId)
                ->where('status', 'final')
                ->get();

            foreach ($finalGames as $g) {
                $homeTeam = $g->homeTeam; $awayTeam = $g->awayTeam;
                if (!$homeTeam || !$awayTeam) continue;
                $homeId = (int) $homeTeam->school_id; $awayId = (int) $awayTeam->school_id;

                foreach ([$homeId, $awayId] as $sid) {
                    if (!isset($table[$sid]) && $schoolsMap->has($sid)) {
                        $s = $schoolsMap->get($sid);
                        $table[$sid] = [
                            'school_id' => (int) $s->id,
                            'name' => $s->name,
                            'logo_path' => $s->logo_path,
                            'wins' => 0,
                            'losses' => 0,
                            'gp' => 0,
                            'win_pct' => 0.0,
                        ];
                    }
                }

                if ($g->home_score === $g->away_score) continue; // ignore ties
                $winnerIsHome = $g->home_score > $g->away_score;
                if (isset($table[$homeId])) $table[$homeId]['gp']++;
                if (isset($table[$awayId])) $table[$awayId]['gp']++;
                if ($winnerIsHome) {
                    if (isset($table[$homeId])) $table[$homeId]['wins']++;
                    if (isset($table[$awayId])) $table[$awayId]['losses']++;
                } else {
                    if (isset($table[$awayId])) $table[$awayId]['wins']++;
                    if (isset($table[$homeId])) $table[$homeId]['losses']++;
                }
            }

            $rows = array_values(array_map(function ($row) {
                $gp = (int) $row['gp'];
                $row['win_pct'] = $gp > 0 ? round(($row['wins'] / $gp) * 100, 1) : 0.0;
                return $row;
            }, $table));

            usort($rows, function ($a, $b) {
                return [$b['wins'], $b['win_pct'], $b['gp'], $a['name']] <=> [$a['wins'], $a['win_pct'], $a['gp'], $b['name']];
            });
            $standings = $rows;
        }


        return Inertia::render('Matches/Index', [
            'upcoming' => $upcoming,
            'finished' => $finished,
            'sports' => $sports,
            'schools' => $schools,
            'standings' => $standings,
            'filters' => [
                'sport_id' => $request->input('sport_id'),
                'school_id' => $request->input('school_id'),
                'winner_school_id' => $request->input('winner_school_id'),
            ],
        ]);
    }
}
