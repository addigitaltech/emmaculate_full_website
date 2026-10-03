<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'school_classes';
    protected $fillable = ['name', 'level', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function arms()
    {
        return $this->belongsToMany(Arm::class, 'class_arms', 'school_class_id', 'arm_id')->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
