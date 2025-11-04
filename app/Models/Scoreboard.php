<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scoreboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'left_school_id', 'right_school_id', 'sport_id',
        'match_label', 'left_score', 'right_score', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'left_score' => 'integer',
        'right_score' => 'integer',
    ];

    public function leftSchool() { return $this->belongsTo(School::class, 'left_school_id'); }
    public function rightSchool() { return $this->belongsTo(School::class, 'right_school_id'); }
    public function sport() { return $this->belongsTo(Sport::class); }
}

