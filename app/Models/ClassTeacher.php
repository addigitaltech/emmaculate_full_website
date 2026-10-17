<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** The teacher in charge of one class arm: the only teacher who sets behaviour ratings and the class teacher's remark. */
class ClassTeacher extends Model
{
    protected $fillable = ['teacher_id', 'school_class_id', 'arm_id'];

    protected static function booted(): void
    {
        static::saving(function (self $classTeacher): void {
            $teacher = Teacher::query()->find($classTeacher->teacher_id);
            if (! $teacher || $teacher->status !== 'active') {
                throw ValidationException::withMessages(['teacher_id' => 'Choose an active teacher.']);
            }
            $class = SchoolClass::query()->find($classTeacher->school_class_id);
            if (! $class) {
                throw ValidationException::withMessages(['school_class_id' => 'Choose an existing class.']);
            }
            $hasArms = $class->arms()->exists();
            if ($hasArms && ! $classTeacher->arm_id) {
                throw ValidationException::withMessages(['arm_id' => 'Choose the arm this teacher is class teacher of.']);
            }
            if ($classTeacher->arm_id && ! $class->arms()->whereKey($classTeacher->arm_id)->exists()) {
                throw ValidationException::withMessages(['arm_id' => 'The selected arm is not part of this class.']);
            }
            $taken = static::query()
                ->where('school_class_id', $classTeacher->school_class_id)
                ->when($classTeacher->arm_id, fn ($query) => $query->where('arm_id', $classTeacher->arm_id), fn ($query) => $query->whereNull('arm_id'))
                ->when($classTeacher->exists, fn ($query) => $query->whereKeyNot($classTeacher->getKey()))
                ->exists();
            if ($taken) {
                throw ValidationException::withMessages(['arm_id' => 'This class arm already has a class teacher.']);
            }
        });
    }

    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function arm() { return $this->belongsTo(Arm::class); }
}
