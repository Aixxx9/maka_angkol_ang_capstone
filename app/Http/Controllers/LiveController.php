<?php
namespace App\Http\Controllers;
use App\Models\Game;
use Inertia\Inertia;


class LiveController extends Controller
{
public function show(Game $game){
$game->load(['homeTeam.school','awayTeam.school','events']);
return Inertia::render('Live/Show', ['game'=>$game]);
}
}