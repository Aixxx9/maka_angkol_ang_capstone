<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'home_team_id',
        'away_team_id',
        'starts_at',
        'venue',
    ];

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'game_team')->withTimestamps()->withPivot('position');
    }
}
