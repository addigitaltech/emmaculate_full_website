<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table): void {
            // The final class (for example SS3): students who finish it graduate and move to the archive.
            $table->boolean('is_graduating')->default(false);
        });
        Schema::table('students', function (Blueprint $table): void {
            $table->timestamp('graduated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('graduated_at');
        });
        Schema::table('school_classes', function (Blueprint $table): void {
            $table->dropColumn('is_graduating');
        });
    }
};
