<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPromotion extends Model
{
    protected $fillable = [
        'student_id', 'source_session_id', 'destination_session_id', 'source_class_id', 'source_arm_id',
        'destination_class_id', 'destination_arm_id', 'decision_status', 'decision_mode', 'eligible',
        'cumulative_average', 'subjects_passed', 'subjects_offered', 'pass_mark_used',
        'min_average_required', 'min_passed_required', 'review_reason', 'decided_by', 'evaluated_at', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'eligible' => 'boolean',
            'cumulative_average' => 'decimal:2',
            'pass_mark_used' => 'decimal:2',
            'min_average_required' => 'decimal:2',
            'subjects_passed' => 'integer',
            'subjects_offered' => 'integer',
            'min_passed_required' => 'integer',
            'evaluated_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function sourceSession() { return $this->belongsTo(AcademicSession::class, 'source_session_id'); }
    public function destinationSession() { return $this->belongsTo(AcademicSession::class, 'destination_session_id'); }
    public function sourceClass() { return $this->belongsTo(SchoolClass::class, 'source_class_id'); }
    public function sourceArm() { return $this->belongsTo(Arm::class, 'source_arm_id'); }
    public function destinationClass() { return $this->belongsTo(SchoolClass::class, 'destination_class_id'); }
    public function destinationArm() { return $this->belongsTo(Arm::class, 'destination_arm_id'); }
    public function decider() { return $this->belongsTo(User::class, 'decided_by'); }
}
