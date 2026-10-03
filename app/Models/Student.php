<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'student_number', 'first_name', 'last_name', 'other_names', 'gender', 'date_of_birth', 'school_class_id', 'arm_id', 'photo_id', 'admission_date', 'status'];
    protected function casts(): array { return ['date_of_birth' => 'date', 'admission_date' => 'date']; }

    protected static function booted(): void
    {
        static::saving(function (self $student): void {
            if (! in_array($student->status, ['active', 'inactive', 'graduated', 'transferred'], true)) {
                throw ValidationException::withMessages(['status' => 'Choose a valid student status.']);
            }
            if ($student->arm_id && (! $student->school_class_id || ! SchoolClass::query()->whereKey($student->school_class_id)->whereHas('arms', fn ($query) => $query->whereKey($student->arm_id))->exists())) {
                throw ValidationException::withMessages(['arm_id' => 'Choose an arm belonging to the selected class.']);
            }
            if ($student->user_id && ! User::query()->whereKey($student->user_id)->whereHas('roles', fn ($query) => $query->where('name', 'Student')->where('guard_name', 'web'))->exists()) {
                throw ValidationException::withMessages(['user_id' => 'The linked account must have the Student role.']);
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function arm() { return $this->belongsTo(Arm::class); }
    public function photo() { return $this->belongsTo(MediaAsset::class, 'photo_id'); }
    public function parents() { return $this->belongsToMany(ParentProfile::class, 'parent_student', 'student_id', 'parent_id')->withPivot('relationship')->withTimestamps(); }
    public function results() { return $this->hasMany(Result::class); }
    public function feeAssignments() { return $this->hasMany(StudentFeeAssignment::class); }
    public function fullName(): string { return trim(implode(' ', array_filter([$this->first_name, $this->other_names, $this->last_name]))); }
}
