<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicProgramme extends Model
{
    protected $fillable = [
        'level', 'title', 'slug', 'intro', 'approach', 'placeholder_note', 'curriculum',
        'image_id', 'image_path', 'is_published', 'sort_order', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return ['approach' => 'array', 'curriculum' => 'array', 'is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    public function image()
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }
}
