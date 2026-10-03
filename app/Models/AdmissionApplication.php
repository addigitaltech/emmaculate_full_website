<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionApplication extends Model
{
    protected $fillable = ['reference', 'applicant_name', 'date_of_birth', 'applying_for', 'guardian_name', 'guardian_email', 'guardian_phone', 'message', 'status', 'metadata', 'submitted_at'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'metadata' => 'array', 'submitted_at' => 'datetime'];
    }
}
