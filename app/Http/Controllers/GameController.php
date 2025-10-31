<?php
namespace App\Http\Controllers;
use App\Models\{Game,GameEvent,Team,Sport};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;


class GameController extends Controller
{
public function schedule(){
$games = Game::with(['homeTeam.school','awayTeam.school','sport'])
->orderBy('starts_at','asc')
->get();
return Inertia::render('Games/Schedule', ['games'=>$games]);
}


public function show(Game $game){
$game->load(['homeTeam.school','awayTeam.school','sport','events','highlights']);
return Inertia::render('Games/Show', ['game'=>$game]);
}


public function create(){
return Inertia::render('Games/Create', [
'teams'=> Team::with('school','sport')->get(),
'sports'=> Sport::all()
]);
}


public function store(Request $req){
$data=$req->validate([
'sport_id'=>'required|exists:sports,id',
'home_team_id'=>'required|exists:teams,id',
'away_team_id'=>'required|exists:teams,id',
'starts_at'=>'required|date',
'venue'=>'nullable'
]);
$data['status']='scheduled';
$game = Game::create($data);
return redirect()->route('games.show',$game);
}


public function addEvent(Request $req, Game $game){
$this->authorize('update',$game); // optional policy
$payload = $req->validate([
'team' => 'required|in:home,away',
'type' => 'required|string',
'value' => 'nullable|integer',
'meta' => 'nullable|array',
]);
$event = $game->events()->create($payload);
if(($payload['type'] ?? null) === 'score'){
if($payload['team']==='home'){ $game->increment('home_score', $payload['value'] ?? 1); }
else { $game->increment('away_score', $payload['value'] ?? 1); }
}
return response()->json(['ok'=>true,'event'=>$event,'game'=>$game->fresh()]);
}


public function setStatus(Request $req, Game $game){
$game->update($req->validate(['status'=>'required|in:scheduled,live,final']));
return back();
}


public function setEmbed(Request $req, Game $game){
$game->update($req->validate(['live_embed_url'=>'nullable|url']));
return back();
}
}