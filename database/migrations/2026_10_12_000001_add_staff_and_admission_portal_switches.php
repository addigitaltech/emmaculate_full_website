<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->boolean('staff_portal_enabled')->default(true);
            $table->boolean('admission_portal_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->dropColumn(['staff_portal_enabled', 'admission_portal_enabled']);
        });
    }
};
