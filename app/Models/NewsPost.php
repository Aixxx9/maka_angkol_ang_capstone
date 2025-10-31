<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsPost extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'author_id','title','slug','excerpt','body','cover_image_path',
        'category','is_featured','views_count','published_at',
    ];

    protected $casts = [
        'is_featured'  => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /* -------- Scopes used in the controller -------- */
    public function scopePublished($q)
    {
        return $q->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeFeatured($q)
    {
        return $q->where('is_featured', true);
    }

    /* -------- Accessors -------- */
    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover_image_path) return null;
        return str_starts_with($this->cover_image_path, '/storage')
            ? $this->cover_image_path
            : '/storage/'.$this->cover_image_path;
    }
}
