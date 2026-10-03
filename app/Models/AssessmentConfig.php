<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AssessmentConfig extends Model
{
    protected $fillable = ['academic_session_id', 'academic_term_id', 'school_class_id', 'subject_id', 'ca1_max', 'ca2_max', 'ca3_max', 'exam_max'];
    protected function casts(): array { return ['ca1_max' => 'integer', 'ca2_max' => 'integer', 'ca3_max' => 'integer', 'exam_max' => 'integer']; }

    protected static function booted(): void
    {
        static::saving(function (self $config): void {
            if (min($config->ca1_max, $config->ca2_max, $config->ca3_max, $config->exam_max) < 0 || max($config->ca1_max, $config->ca2_max, $config->ca3_max, $config->exam_max) > 100 || array_sum($config->maxima()) !== 100) {
                throw ValidationException::withMessages(['ca1_max' => 'Assessment components must be between 0 and 100 and sum to exactly 100.']);
            }
            if (! AcademicTerm::query()->whereKey($config->academic_term_id)->where('academic_session_id', $config->academic_session_id)->exists()) {
                throw ValidationException::withMessages(['academic_term_id' => 'The term must belong to the selected session.']);
            }
            $subject = Subject::query()->find($config->subject_id);
            if (! $subject || ($subject->school_class_id && (int) $subject->school_class_id !== (int) $config->school_class_id)) {
                throw ValidationException::withMessages(['subject_id' => 'The subject must be valid for the selected class.']);
            }
        });
    }

    public function session() { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function term() { return $this->belongsTo(AcademicTerm::class, 'academic_term_id'); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'school_class_id'); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function maxima(): array { return ['ca1' => (int) $this->ca1_max, 'ca2' => (int) $this->ca2_max, 'ca3' => (int) $this->ca3_max, 'exam' => (int) $this->exam_max]; }
}
