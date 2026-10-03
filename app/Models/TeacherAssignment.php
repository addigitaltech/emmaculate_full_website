<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class TeacherAssignment extends Model
{
    protected $fillable = ['teacher_id', 'school_class_id', 'arm_id', 'subject_id'];

    protected static function booted(): void
    {
        static::saving(function (self $assignment): void {
            $teacher = Teacher::query()->find($assignment->teacher_id);
            $subject = Subject::query()->find($assignment->subject_id);
            if (! $teacher || $teacher->status !== 'active' || ! $subject) {
                throw ValidationException::withMessages(['teacher_id' => 'Choose an active teacher and an existing subject.']);
            }
            if ($assignment->arm_id && ! SchoolClass::query()->whereKey($assignment->school_class_id)->whereHas('arms', fn ($query) => $query->whereKey($assignment->arm_id))->exists()) {
                throw ValidationException::withMessages(['arm_id' => 'The selected arm is not part of this class.']);
            }
            if ($subject->school_class_id && (int) $subject->school_class_id !== (int) $assignment->school_class_id) {
                throw ValidationException::withMessages(['subject_id' => 'The selected subject does not belong to this class.']);
            }
            if ($subject->arm_id && (int) $subject->arm_id !== (int) $assignment->arm_id) {
                throw ValidationException::withMessages(['subject_id' => 'The selected subject is scoped to a different class arm.']);
            }
        });
    }

    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function arm() { return $this->belongsTo(Arm::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
}
