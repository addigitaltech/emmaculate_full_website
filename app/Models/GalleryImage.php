<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $fillable = ['album_id', 'media_id', 'image_path', 'caption', 'alt_text', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    public function album()
    {
        return $this->belongsTo(GalleryAlbum::class, 'album_id');
    }

    public function media()
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }
}
