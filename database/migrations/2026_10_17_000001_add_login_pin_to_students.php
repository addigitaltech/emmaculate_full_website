<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A student signs in with admission number + surname + a 6-digit PIN printed on a slip.
        // Only a hash of the PIN is stored; failed attempts and temporary locks are tracked per student.
        Schema::table('students', function (Blueprint $table): void {
            $table->string('pin_hash')->nullable();
            $table->timestamp('pin_set_at')->nullable();
            $table->unsignedSmallInteger('pin_failed_attempts')->default(0);
            $table->timestamp('pin_locked_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn(['pin_hash', 'pin_set_at', 'pin_failed_attempts', 'pin_locked_until']);
        });
    }
};
