<?php

namespace App\Models;

use App\Domain\Website\Support\SafePublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class FooterSection extends Model
{
    protected $fillable = ['title', 'links', 'is_visible', 'sort_order'];
    protected function casts(): array { return ['links' => 'array', 'is_visible' => 'boolean', 'sort_order' => 'integer']; }

    protected static function booted(): void
    {
        static::saving(function (self $section): void {
            foreach ($section->links ?? [] as $index => $link) {
                if (! is_array($link) || ! SafePublicUrl::allows($link['url'] ?? null)) {
                    throw ValidationException::withMessages(['links.'.$index.'.url' => 'Footer links must be internal paths or HTTPS URLs without credentials.']);
                }
            }
        });
    }
}
