<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeBand extends Model
{
    protected $fillable = ['min_score', 'max_score', 'grade', 'remark', 'sort_order'];

    protected function casts(): array
    {
        return ['min_score' => 'integer', 'max_score' => 'integer', 'sort_order' => 'integer'];
    }
}
