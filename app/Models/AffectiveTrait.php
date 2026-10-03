<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffectiveTrait extends Model
{
    protected $fillable = ['name', 'category', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
