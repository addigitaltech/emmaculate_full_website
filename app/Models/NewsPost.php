<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'author_id', 'cover_image_id', 'title', 'slug', 'subtitle', 'excerpt', 'content', 'category', 'tags',
        'status', 'is_featured', 'published_at', 'expires_at', 'seo_title', 'seo_description', 'social_image_id',
    ];

    protected function casts(): array
    {
        return ['tags' => 'array', 'is_featured' => 'boolean', 'published_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function coverImage()
    {
        return $this->belongsTo(MediaAsset::class, 'cover_image_id');
    }
}
