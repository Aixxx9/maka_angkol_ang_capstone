<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class Highlight extends Model {
protected $fillable = ['game_id','title','description','video_url'];
public function game(){ return $this->belongsTo(Game::class); }
}