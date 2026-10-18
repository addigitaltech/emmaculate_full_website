<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The school's Student ID prefix (e.g. EAC in EAC/2627/0001) is a setting, so the site can be reused by any school.
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->string('student_id_prefix', 12)->default('EAC');
        });

        // One running counter per prefix and session, locked while a new Student ID is handed out so two students
        // entered at the same moment can never receive the same number.
        Schema::create('student_id_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('prefix', 12);
            $table->string('session_code', 8);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['prefix', 'session_code'], 'student_id_counter_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_id_counters');
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->dropColumn('student_id_prefix');
        });
    }
};
