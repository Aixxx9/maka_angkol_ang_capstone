<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'competition_id',
        'round',
        'bracket_pos',
        'home_team_id',
        'away_team_id',
        'starts_at',
        'venue',
        // Allow mass assignment for finalize and embeds
        'status',
        'home_score',
        'away_score',
        'live_embed_url',
        'live_title',
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
        return $this->belongsToMany(Team::class, 'game_team')
            ->withTimestamps()
            ->withPivot('position', 'score');
    }

    public function events()
    {
        return $this->hasMany(GameEvent::class);
    }

    public function highlights()
    {
        return $this->hasMany(Highlight::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }
}
