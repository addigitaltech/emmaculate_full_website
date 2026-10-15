<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->string('promotion_mode', 20)->default('manual');
            $table->decimal('promotion_min_average', 5, 2)->nullable();
            $table->unsignedSmallInteger('promotion_min_subjects_passed')->nullable();
        });

        Schema::table('school_classes', function (Blueprint $table): void {
            $table->foreignId('next_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
        });

        Schema::create('student_promotions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('destination_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('source_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('source_arm_id')->nullable()->constrained('arms')->nullOnDelete();
            $table->foreignId('destination_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('destination_arm_id')->nullable()->constrained('arms')->nullOnDelete();
            $table->string('decision_status', 20)->default('review')->index();
            $table->string('decision_mode', 20)->default('manual');
            $table->boolean('eligible')->default(false);
            $table->decimal('cumulative_average', 5, 2)->nullable();
            $table->unsignedSmallInteger('subjects_passed')->default(0);
            $table->unsignedSmallInteger('subjects_offered')->default(0);
            $table->decimal('pass_mark_used', 5, 2)->nullable();
            $table->decimal('min_average_required', 5, 2)->nullable();
            $table->unsignedSmallInteger('min_passed_required')->nullable();
            $table->text('review_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['student_id', 'source_session_id', 'destination_session_id'],
                'student_promotion_transition_unique'
            );
            $table->index(['source_session_id', 'destination_session_id', 'decision_status'], 'student_promotion_transition_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_promotions');

        Schema::table('school_classes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('next_class_id');
        });

        Schema::table('school_settings', function (Blueprint $table): void {
            $table->dropColumn(['promotion_mode', 'promotion_min_average', 'promotion_min_subjects_passed']);
        });
    }
};
