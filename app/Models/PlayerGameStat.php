<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerGameStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'athlete_id',
        'points',
        'rebounds',
        'assists',
        'fg_percent',
        'game_date',
        'metrics',
    ];

    protected $casts = [
        'metrics' => 'array',
    ];

    public function athlete()
    {
        return $this->belongsTo(Athlete::class);
    }
}
