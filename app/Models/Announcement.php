<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'body', 'image_id', 'link_label', 'link_url', 'status', 'starts_at', 'expires_at', 'sort_order'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'sort_order' => 'integer'];
    }

    public function scopeVisibleNow(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function image()
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }
}
