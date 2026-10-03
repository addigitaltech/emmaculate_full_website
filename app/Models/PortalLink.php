<?php

namespace App\Models;

use App\Domain\Website\Support\SafePublicUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class PortalLink extends Model
{
    protected $fillable = ['key', 'title', 'description', 'url', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $link): void {
            if (! in_array($link->status, ['live', 'coming_soon', 'disabled'], true)) {
                throw ValidationException::withMessages(['status' => 'Choose Live, Coming Soon, or Disabled.']);
            }
            if (! SafePublicUrl::allows($link->url)) {
                throw ValidationException::withMessages(['url' => 'Use an internal path or an HTTPS link without credentials.']);
            }
            if ($link->status === 'live' && blank($link->url)) {
                throw ValidationException::withMessages(['url' => 'A Live portal link must have a destination.']);
            }
        });
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ['live', 'coming_soon'])->orderBy('sort_order')->orderBy('title');
    }

    public function isLive(): bool
    {
        return $this->status === 'live' && filled($this->url);
    }
}
