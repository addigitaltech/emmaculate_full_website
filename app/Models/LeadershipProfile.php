<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadershipProfile extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'title', 'photo_id', 'photo_path', 'biography', 'qualifications', 'is_visible', 'sort_order'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    public function photo()
    {
        return $this->belongsTo(MediaAsset::class, 'photo_id');
    }
}
