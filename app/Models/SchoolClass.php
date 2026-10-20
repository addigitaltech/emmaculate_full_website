<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SchoolClass extends Model
{
    protected $table = 'school_classes';
    protected $fillable = ['name', 'level', 'is_active', 'next_class_id', 'is_graduating'];

    protected static function booted(): void
    {
        static::saving(function (self $schoolClass): void {
            // A final class has nowhere to be promoted to: its students graduate.
            if ($schoolClass->is_graduating) {
                $schoolClass->next_class_id = null;
            }

            if ($schoolClass->next_class_id && (int) $schoolClass->next_class_id === (int) $schoolClass->id) {
                throw ValidationException::withMessages(['next_class_id' => 'A class cannot be configured as its own next class.']);
            }

            if ($schoolClass->next_class_id) {
                $seen = $schoolClass->id ? [(int) $schoolClass->id => true] : [];
                $nextId = (int) $schoolClass->next_class_id;
                while ($nextId > 0) {
                    if (isset($seen[$nextId])) {
                        throw ValidationException::withMessages(['next_class_id' => 'Class progression cannot contain a cycle.']);
                    }
                    $seen[$nextId] = true;
                    $nextId = (int) (static::query()->whereKey($nextId)->value('next_class_id') ?? 0);
                }
            }
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_graduating' => 'boolean'];
    }

    public function arms()
    {
        return $this->belongsToMany(Arm::class, 'class_arms', 'school_class_id', 'arm_id')->withTimestamps();
    }

    public function nextClass()
    {
        return $this->belongsTo(self::class, 'next_class_id');
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
