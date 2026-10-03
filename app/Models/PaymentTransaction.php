<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    public const STATUSES = ['pending', 'processing', 'successful', 'failed', 'cancelled', 'refunded'];
    public const REFUND_STATUSES = ['none', 'processing', 'accepted', 'needs_attention', 'failed', 'unknown', 'completed'];

    protected $fillable = ['fee_assignment_id', 'student_id', 'initiated_by', 'gateway', 'reference', 'provider_reference', 'idempotency_key', 'amount', 'currency', 'status', 'checkout_url', 'safe_metadata', 'attempt_count', 'verified_at', 'refund_status', 'refund_reference', 'refund_provider_status', 'refund_requested_at', 'refunded_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'safe_metadata' => 'array', 'attempt_count' => 'integer', 'verified_at' => 'datetime', 'refund_requested_at' => 'datetime', 'refunded_at' => 'datetime'];
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function feeAssignment() { return $this->belongsTo(StudentFeeAssignment::class, 'fee_assignment_id'); }
    public function initiator() { return $this->belongsTo(User::class, 'initiated_by'); }
    public function events() { return $this->hasMany(PaymentEvent::class); }
    public function receipt() { return $this->hasOne(PaymentReceipt::class); }
}
