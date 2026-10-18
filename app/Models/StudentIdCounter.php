<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Running number behind Student IDs such as EAC/2627/0001, one row per prefix and session. */
class StudentIdCounter extends Model
{
    protected $fillable = ['prefix', 'session_code', 'last_number'];
}
