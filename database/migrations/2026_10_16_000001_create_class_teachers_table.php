<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One class teacher per class arm. A teacher may be class teacher of several arms or classes.
        // arm_id stays empty only for a class that has no arms.
        Schema::create('class_teachers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('arm_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['school_class_id', 'arm_id'], 'class_teacher_class_arm_unique');
            $table->index('teacher_id', 'class_teacher_teacher_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_teachers');
    }
};
