<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Off by default so nothing changes until the school has issued PIN slips and switches the PIN on.
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->boolean('require_pin_student_login')->default(false);
            $table->boolean('require_pin_result_check')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->dropColumn(['require_pin_student_login', 'require_pin_result_check']);
        });
    }
};
