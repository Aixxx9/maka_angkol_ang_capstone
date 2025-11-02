<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NewsPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image_path',
        'category',
        'is_featured',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    // === SCOPES ===
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // === ACCESSORS ===
    public function getCoverUrlAttribute()
    {
        return $this->cover_image_path
            ? Storage::url($this->cover_image_path)
            : '/images/default-logo.png';
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    public function getRouteKeyName()
{
    return 'slug';
}

}
