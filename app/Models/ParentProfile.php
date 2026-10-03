<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ParentProfile extends Model
{
    use SoftDeletes;
    protected $table = 'parents';
    protected $fillable = ['user_id', 'full_name', 'email', 'phone'];

    protected static function booted(): void
    {
        static::saving(function (self $profile): void {
            if ($profile->user_id && ! User::query()->whereKey($profile->user_id)->whereHas('roles', fn ($query) => $query->where('name', 'Parent')->where('guard_name', 'web'))->exists()) {
                throw ValidationException::withMessages(['user_id' => 'The linked account must have the Parent role.']);
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function students() { return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')->withPivot('relationship')->withTimestamps(); }
}
