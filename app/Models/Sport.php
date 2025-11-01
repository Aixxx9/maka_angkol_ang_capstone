<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'icon_path',
        'team_count',
        'athlete_count',
        'stat_fields',
    ];

    protected $casts = [
        'stat_fields' => 'array',
    ];
}
