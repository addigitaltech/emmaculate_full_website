<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsSection extends Model
{
    protected $fillable = ['page_id', 'section_key', 'title', 'description', 'content', 'is_visible', 'sort_order'];

    protected function casts(): array
    {
        return ['content' => 'array', 'is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    public function page()
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }
}
