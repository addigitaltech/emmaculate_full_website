<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GalleryAlbum extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'slug', 'description', 'cover_image_id', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function coverImage()
    {
        return $this->belongsTo(MediaAsset::class, 'cover_image_id');
    }

    public function images()
    {
        return $this->hasMany(GalleryImage::class, 'album_id')->where('is_visible', true)->orderBy('sort_order');
    }
}
