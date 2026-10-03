<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class StudentFeeAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = ['student_id', 'fee_type_id', 'academic_session_id', 'academic_term_id', 'amount_due', 'currency', 'due_date', 'status', 'description', 'assigned_by'];
    protected function casts(): array { return ['amount_due' => 'decimal:2', 'due_date' => 'date']; }

    protected static function booted(): void
    {
        static::creating(function (self $assignment): void {
            $assignment->status = 'unpaid';
            $assignment->assigned_by = auth()->id();
        });
        static::saving(function (self $assignment): void {
            if (! in_array($assignment->status, ['unpaid', 'overdue', 'paid'], true)) {
                throw ValidationException::withMessages(['status' => 'Invalid fee assignment status.']);
            }
            if ((float) $assignment->amount_due <= 0 || strtoupper((string) $assignment->currency) !== 'NGN') {
                throw ValidationException::withMessages(['amount_due' => 'Fee amount must be positive and online fee assignments use NGN.']);
            }
            if ($assignment->academic_term_id && ! AcademicTerm::query()->whereKey($assignment->academic_term_id)->where('academic_session_id', $assignment->academic_session_id)->exists()) {
                throw ValidationException::withMessages(['academic_term_id' => 'Choose a term belonging to the selected academic session.']);
            }
            if ($assignment->exists && $assignment->getOriginal('status') === 'paid') {
                $confirmedRefundReversal = in_array($assignment->status, ['unpaid', 'overdue'], true)
                    && $assignment->transactions()->where('status', 'refunded')->exists()
                    && ! $assignment->transactions()->where('status', 'successful')->exists();
                if (($assignment->status !== 'paid' && ! $confirmedRefundReversal) || $assignment->isDirty(['student_id', 'fee_type_id', 'academic_session_id', 'academic_term_id', 'amount_due', 'currency'])) {
                    throw ValidationException::withMessages(['status' => 'Paid fee assignments are immutable.']);
                }
            }
            if ($assignment->exists && $assignment->transactions()->exists() && $assignment->isDirty(['student_id', 'fee_type_id', 'academic_session_id', 'academic_term_id', 'amount_due', 'currency'])) {
                throw ValidationException::withMessages(['amount_due' => 'Fee identity and amount are locked after a payment attempt exists.']);
            }
            if ($assignment->status === 'paid' && ! $assignment->transactions()->where('status', 'successful')->exists()) {
                throw ValidationException::withMessages(['status' => 'Only verified payment settlement can mark a fee as paid.']);
            }
        });
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function feeType() { return $this->belongsTo(FeeType::class); }
    public function academicSession() { return $this->belongsTo(AcademicSession::class); }
    public function academicTerm() { return $this->belongsTo(AcademicTerm::class); }
    public function transactions() { return $this->hasMany(PaymentTransaction::class, 'fee_assignment_id'); }
}
