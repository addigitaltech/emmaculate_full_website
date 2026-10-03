<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Teacher extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id', 'staff_number', 'first_name', 'last_name', 'other_names', 'email', 'phone', 'gender', 'status'];

    protected static function booted(): void
    {
        static::saving(function (self $teacher): void {
            if (! in_array($teacher->status, ['active', 'inactive'], true)) {
                throw ValidationException::withMessages(['status' => 'Choose a valid teacher status.']);
            }
            if ($teacher->user_id && ! User::query()->whereKey($teacher->user_id)->whereHas('roles', fn ($query) => $query->where('name', 'Teacher')->where('guard_name', 'web'))->exists()) {
                throw ValidationException::withMessages(['user_id' => 'The linked account must have the Teacher role.']);
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function assignments() { return $this->hasMany(TeacherAssignment::class); }
    public function results() { return $this->hasMany(Result::class); }
    public function fullName(): string { return trim(implode(' ', array_filter([$this->first_name, $this->other_names, $this->last_name]))); }
}
