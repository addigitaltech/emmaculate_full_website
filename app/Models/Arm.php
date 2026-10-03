<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Arm extends Model
{
    protected $fillable = ['name'];

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_arms', 'arm_id', 'school_class_id')->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
}
