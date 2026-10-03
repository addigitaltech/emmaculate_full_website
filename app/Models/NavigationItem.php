<?php

namespace App\Models;

use App\Domain\Website\Support\SafePublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class NavigationItem extends Model
{
    protected $fillable = ['parent_id', 'menu', 'label', 'url', 'description', 'is_visible', 'sort_order'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if (! SafePublicUrl::allows($item->url)) {
                throw ValidationException::withMessages(['url' => 'Use an internal path or an HTTPS link without credentials.']);
            }
            if ($item->parent_id) {
                $parent = static::query()->find($item->parent_id);
                if (! $parent || $parent->id === $item->id || $parent->menu !== $item->menu || $parent->parent_id !== null) {
                    throw ValidationException::withMessages(['parent_id' => 'Choose a root item from the same menu.']);
                }
            }
        });
    }

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->where('is_visible', true)->orderBy('sort_order'); }
}
