<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The part of one payment that went towards one fee. */
class PaymentAllocation extends Model
{
    protected $fillable = ['payment_transaction_id', 'fee_assignment_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function transaction() { return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id'); }
    public function feeAssignment() { return $this->belongsTo(StudentFeeAssignment::class, 'fee_assignment_id'); }
}
