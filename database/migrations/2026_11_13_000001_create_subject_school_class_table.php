<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_school_class', function (Blueprint $table): void {
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['subject_id', 'school_class_id']);
        });

        DB::table('subjects')->whereNotNull('school_class_id')->orderBy('id')->get(['id', 'school_class_id'])->each(function (object $subject): void {
            DB::table('subject_school_class')->insertOrIgnore([
                'subject_id' => $subject->id,
                'school_class_id' => $subject->school_class_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_school_class');
    }
};
