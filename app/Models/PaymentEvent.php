<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    protected $fillable = ['payment_transaction_id', 'gateway', 'provider_event_id', 'event_type', 'payload_hash', 'processing_status', 'failure_reason', 'processed_at'];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
