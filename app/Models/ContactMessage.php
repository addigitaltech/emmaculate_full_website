<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'status', 'ip_hash', 'received_at'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }
}
