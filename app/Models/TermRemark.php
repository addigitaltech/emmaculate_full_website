<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TermRemark extends Model
{
    protected $fillable = ['student_id', 'academic_session_id', 'academic_term_id', 'teacher_remark', 'principal_remark'];

    protected static function booted(): void
    {
        static::saving(function (self $remark): void {
            if (! AcademicTerm::query()->whereKey($remark->academic_term_id)->where('academic_session_id', $remark->academic_session_id)->exists()) {
                throw ValidationException::withMessages(['academic_term_id' => 'The term must belong to the selected session.']);
            }
            if (Auth::id()) $remark->updated_by = Auth::id();
        });
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function session() { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function term() { return $this->belongsTo(AcademicTerm::class, 'academic_term_id'); }
    public function editor() { return $this->belongsTo(User::class, 'updated_by'); }
}
