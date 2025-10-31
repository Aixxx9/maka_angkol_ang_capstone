<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class GameEvent extends Model {
public $timestamps = false;
protected $fillable = ['game_id','team','type','value','meta','occurred_at'];
protected $casts = ['meta' => 'array','occurred_at' => 'datetime'];
public function game(){ return $this->belongsTo(Game::class); }
}