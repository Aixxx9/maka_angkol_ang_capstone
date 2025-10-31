<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class Game extends Model {
protected $fillable = ['sport_id','home_team_id','away_team_id','starts_at','venue','status','home_score','away_score','live_embed_url'];
protected $casts = ['starts_at' => 'datetime'];
public function sport(){ return $this->belongsTo(Sport::class); }
public function homeTeam(){ return $this->belongsTo(Team::class,'home_team_id'); }
public function awayTeam(){ return $this->belongsTo(Team::class,'away_team_id'); }
public function events(){ return $this->hasMany(GameEvent::class); }
public function highlights(){ return $this->hasMany(Highlight::class); }
}