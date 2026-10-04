<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'image_id', 'title', 'slug', 'description', 'starts_at', 'ends_at', 'location',
        'registration_url', 'status', 'is_featured', 'published_at', 'expires_at', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'published_at' => 'datetime', 'expires_at' => 'datetime', 'is_featured' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('starts_at')
                ->orWhere('starts_at', '>=', now()->startOfDay())
                ->orWhere('ends_at', '>=', now());
        });
    }

    public function image()
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }
}
