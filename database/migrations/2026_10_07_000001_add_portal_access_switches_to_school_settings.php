<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->boolean('student_portal_enabled')->default(true);
            $table->boolean('parent_portal_enabled')->default(true);
            $table->boolean('public_result_check_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->dropColumn(['student_portal_enabled', 'parent_portal_enabled', 'public_result_check_enabled']);
        });
    }
};
