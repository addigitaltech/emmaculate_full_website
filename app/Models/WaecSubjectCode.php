<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaecSubjectCode extends Model
{
    protected $fillable = ['subject_name', 'code', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
}
