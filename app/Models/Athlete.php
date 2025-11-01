<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Athlete extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'number',
        'position',
        'team_id',
        'sport_id',
        'school_id',
        'avatar_path',
    ];

    /**
     * Relationships
     */
    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Accessors & Helpers
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            // Converts storage path -> public URL (e.g., /storage/athletes/...)
            return Storage::url($this->avatar_path);
        }

        // Default image if no avatar
        return asset('images/user.png');
    }
}
