<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $fillable = [
        'name',
        'sport_id',
        'bracket_type',
        'status',
        'season',
    ];

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
