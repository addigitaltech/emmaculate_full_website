<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class StudentFeeAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = ['student_id', 'fee_type_id', 'academic_session_id', 'academic_term_id', 'amount_due', 'currency', 'due_date', 'status', 'description', 'assigned_by'];
    protected function casts(): array { return ['amount_due' => 'decimal:2', 'amount_paid' => 'decimal:2', 'due_date' => 'date']; }

    /** What is still owed on this fee. */
    public function balance(): float
    {
        return max(0.0, round((float) $this->amount_due - (float) $this->amount_paid, 2));
    }

    /** True when a verified (successful) payment has been recorded for this fee, either directly or as part of a larger payment. */
    public function hasVerifiedPayment(): bool
    {
        return $this->transactions()->where('status', 'successful')->exists()
            || $this->allocations()->whereHas('transaction', fn ($query) => $query->where('status', 'successful'))->exists();
    }

    public function hasRefundedPayment(): bool
    {
        return $this->transactions()->where('status', 'refunded')->exists()
            || $this->allocations()->whereHas('transaction', fn ($query) => $query->where('status', 'refunded'))->exists();
    }

    public function hasPaymentAttempt(): bool
    {
        return $this->transactions()->exists() || $this->allocations()->exists();
    }

    protected static function booted(): void
    {
        static::creating(function (self $assignment): void {
            $assignment->status = 'unpaid';
            $assignment->assigned_by = auth()->id();
        });
        static::saving(function (self $assignment): void {
            if (! in_array($assignment->status, ['unpaid', 'overdue', 'partial', 'paid'], true)) {
                throw ValidationException::withMessages(['status' => 'Invalid fee assignment status.']);
            }
            if ((float) $assignment->amount_due <= 0 || strtoupper((string) $assignment->currency) !== 'NGN') {
                throw ValidationException::withMessages(['amount_due' => 'Fee amount must be positive and online fee assignments use NGN.']);
            }
            if ($assignment->academic_term_id && ! AcademicTerm::query()->whereKey($assignment->academic_term_id)->where('academic_session_id', $assignment->academic_session_id)->exists()) {
                throw ValidationException::withMessages(['academic_term_id' => 'Choose a term belonging to the selected academic session.']);
            }
            if ($assignment->exists && $assignment->getOriginal('status') === 'paid') {
                $confirmedRefundReversal = in_array($assignment->status, ['unpaid', 'overdue', 'partial'], true)
                    && $assignment->hasRefundedPayment()
                    && (float) $assignment->amount_paid < (float) $assignment->amount_due;
                if (($assignment->status !== 'paid' && ! $confirmedRefundReversal) || $assignment->isDirty(['student_id', 'fee_type_id', 'academic_session_id', 'academic_term_id', 'amount_due', 'currency'])) {
                    throw ValidationException::withMessages(['status' => 'Paid fee assignments are immutable.']);
                }
            }
            if ($assignment->exists && $assignment->hasPaymentAttempt() && $assignment->isDirty(['student_id', 'fee_type_id', 'academic_session_id', 'academic_term_id', 'amount_due', 'currency'])) {
                throw ValidationException::withMessages(['amount_due' => 'Fee identity and amount are locked after a payment attempt exists.']);
            }
            if ($assignment->status === 'paid' && ! $assignment->hasVerifiedPayment()) {
                throw ValidationException::withMessages(['status' => 'Only verified payment settlement can mark a fee as paid.']);
            }
        });
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function feeType() { return $this->belongsTo(FeeType::class); }
    public function academicSession() { return $this->belongsTo(AcademicSession::class); }
    public function academicTerm() { return $this->belongsTo(AcademicTerm::class); }
    public function transactions() { return $this->hasMany(PaymentTransaction::class, 'fee_assignment_id'); }
    public function allocations() { return $this->hasMany(PaymentAllocation::class, 'fee_assignment_id'); }
}
