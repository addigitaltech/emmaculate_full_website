<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'student_id', 'subject_id', 'teacher_id', 'school_class_id', 'arm_id', 'academic_session_id',
        'academic_term_id', 'ca1_score', 'ca2_score', 'ca3_score', 'exam_score', 'total_score',
        'is_offered', 'grade', 'remark', 'teacher_remark', 'principal_remark', 'status', 'published_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'ca1_score' => 'decimal:2', 'ca2_score' => 'decimal:2', 'ca3_score' => 'decimal:2',
            'exam_score' => 'decimal:2', 'total_score' => 'decimal:2', 'is_offered' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function arm() { return $this->belongsTo(Arm::class); }
    public function academicSession() { return $this->belongsTo(AcademicSession::class); }
    public function academicTerm() { return $this->belongsTo(AcademicTerm::class); }
    public function publisher() { return $this->belongsTo(User::class, 'published_by'); }
}
