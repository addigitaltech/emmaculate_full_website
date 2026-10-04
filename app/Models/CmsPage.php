<?php

namespace App\Models;

use App\Domain\Website\Support\SafePublicUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class CmsPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'created_by', 'updated_by', 'title', 'slug', 'eyebrow', 'excerpt', 'content', 'content_blocks',
        'featured_media_id', 'status', 'published_at', 'seo_title', 'seo_description', 'canonical_url',
        'social_image_id', 'metadata',
    ];

    protected function casts(): array
    {
        return ['content_blocks' => 'array', 'metadata' => 'array', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $page): void {
            $page->created_by ??= auth()->id();
        });
        static::saving(function (self $page): void {
            $page->updated_by = auth()->id() ?? $page->updated_by;
            if (filled($page->canonical_url) && ! SafePublicUrl::allows($page->canonical_url)) {
                throw ValidationException::withMessages(['canonical_url' => 'Canonical URL must be an internal path or an HTTPS URL without credentials.']);
            }
            $blocks = $page->content_blocks ?? [];
            if (! is_array($blocks)) {
                throw ValidationException::withMessages(['content_blocks' => 'Content blocks must use the structured block editor.']);
            }
            foreach ($blocks as $index => $block) {
                if (! is_array($block) || ! in_array($block['type'] ?? null, ['heading', 'paragraph', 'list', 'quote', 'image', 'cta', 'section'], true)) {
                    throw ValidationException::withMessages(['content_blocks' => 'A content block type is invalid.']);
                }
                if (isset($block['cta_url']) && ! SafePublicUrl::allows((string) $block['cta_url'])) {
                    throw ValidationException::withMessages(['content_blocks.'.$index.'.cta_url' => 'Use an internal path or an HTTPS link without credentials.']);
                }
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function featuredMedia()
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    public function sections()
    {
        return $this->hasMany(CmsSection::class, 'page_id')->orderBy('sort_order');
    }
}
