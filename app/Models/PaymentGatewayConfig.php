<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayConfig extends Model
{
    protected $fillable = ['driver', 'is_enabled', 'supports_online_checkout', 'supports_refunds', 'display_name', 'configuration_status', 'public_settings'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'supports_online_checkout' => 'boolean', 'supports_refunds' => 'boolean', 'public_settings' => 'array'];
    }
}
