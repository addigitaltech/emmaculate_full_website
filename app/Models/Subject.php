<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Subject extends Model
{
    protected $fillable = ['code', 'name', 'school_class_id', 'arm_id', 'status'];

    protected static function booted(): void
    {
        static::saving(function (self $subject): void {
            if (! in_array($subject->status, ['active', 'inactive'], true)) {
                throw ValidationException::withMessages(['status' => 'Choose a valid subject status.']);
            }
            if ($subject->arm_id && (! $subject->school_class_id || ! SchoolClass::query()->whereKey($subject->school_class_id)->whereHas('arms', fn ($query) => $query->whereKey($subject->arm_id))->exists())) {
                throw ValidationException::withMessages(['arm_id' => 'Choose an arm belonging to the selected class.']);
            }
        });
    }

    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function arm() { return $this->belongsTo(Arm::class); }
    public function teacherAssignments() { return $this->hasMany(TeacherAssignment::class); }
    public function results() { return $this->hasMany(Result::class); }
}
