<?php

namespace App\Models;

use App\Domain\Website\Support\SafePublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class HeroSlide extends Model
{
    protected $fillable = ['eyebrow', 'heading', 'body', 'image_id', 'image_path', 'image_alt', 'mobile_image_path', 'cta_label', 'cta_url', 'secondary_cta_label', 'secondary_cta_url', 'is_enabled', 'sort_order', 'seo_title', 'seo_description'];

    protected function casts(): array { return ['is_enabled' => 'boolean', 'sort_order' => 'integer']; }

    protected static function booted(): void
    {
        static::saving(function (self $slide): void {
            foreach (['cta_url', 'secondary_cta_url'] as $field) {
                if (! SafePublicUrl::allows($slide->{$field})) {
                    throw ValidationException::withMessages([$field => 'Use an internal path or an HTTPS link without credentials.']);
                }
            }
            if ($slide->image_id) {
                $asset = MediaAsset::query()->where('mime_type', 'like', 'image/%')->find($slide->image_id);
                if (! $asset) {
                    throw ValidationException::withMessages(['image_id' => 'Choose a supported image from the media library.']);
                }
                $slide->image_path = $asset->path;
                $slide->image_alt = $slide->image_alt ?: $asset->alt_text;
            }
        });
    }

    public function image() { return $this->belongsTo(MediaAsset::class, 'image_id'); }
}
