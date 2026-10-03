<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentReceipt extends Model
{
    protected $fillable = ['payment_transaction_id', 'receipt_number', 'storage_path', 'issued_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
