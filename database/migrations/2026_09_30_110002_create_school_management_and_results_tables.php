<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(false)->index();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_terms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('sequence');
            $table->boolean('is_current')->default(false)->index();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
            $table->unique(['academic_session_id', 'sequence']);
            $table->unique(['academic_session_id', 'name']);
        });

        Schema::create('school_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('level')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('arms', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('class_arms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('arm_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['school_class_id', 'arm_id']);
        });

        Schema::create('teachers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('staff_number')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('student_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('arm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('photo_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->date('admission_date')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['school_class_id', 'arm_id', 'status']);
        });

        Schema::create('parents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('parent_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->nullable();
            $table->timestamps();
            $table->unique(['parent_id', 'student_id']);
        });

        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('arm_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['name', 'school_class_id', 'arm_id']);
        });

        Schema::create('teacher_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('arm_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['teacher_id', 'school_class_id', 'arm_id', 'subject_id'], 'teacher_assignment_unique');
            $table->index(['teacher_id', 'school_class_id', 'subject_id']);
        });

        Schema::create('assessment_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('ca1_max')->default(40);
            $table->unsignedTinyInteger('ca2_max')->default(0);
            $table->unsignedTinyInteger('ca3_max')->default(0);
            $table->unsignedTinyInteger('exam_max')->default(60);
            $table->timestamps();
            $table->unique(['academic_session_id', 'academic_term_id', 'school_class_id', 'subject_id'], 'assessment_config_unique');
        });

        Schema::create('grade_bands', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('min_score');
            $table->unsignedTinyInteger('max_score');
            $table->string('grade', 10);
            $table->string('remark');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['min_score', 'max_score']);
        });

        Schema::create('results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('arm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->decimal('ca1_score', 6, 2)->default(0);
            $table->decimal('ca2_score', 6, 2)->default(0);
            $table->decimal('ca3_score', 6, 2)->default(0);
            $table->decimal('exam_score', 6, 2)->default(0);
            $table->decimal('total_score', 7, 2)->default(0);
            $table->boolean('is_offered')->default(true);
            $table->string('grade', 10)->nullable();
            $table->string('remark')->nullable();
            $table->string('teacher_remark')->nullable();
            $table->string('principal_remark')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'subject_id', 'academic_session_id', 'academic_term_id'], 'student_result_unique');
            $table->index(['school_class_id', 'arm_id', 'academic_session_id', 'academic_term_id', 'status']);
        });

        Schema::create('affective_traits', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->default('affective');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('affective_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trait_id')->constrained('affective_traits')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->foreignId('rated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'trait_id', 'academic_session_id', 'academic_term_id'], 'affective_rating_unique');
        });

        Schema::create('term_remarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->text('teacher_remark')->nullable();
            $table->text('principal_remark')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'academic_session_id', 'academic_term_id'], 'term_remark_unique');
        });
    }

    public function down(): void
    {
        foreach ([
            'term_remarks', 'affective_ratings', 'affective_traits', 'results', 'grade_bands',
            'assessment_configs', 'teacher_assignments', 'subjects', 'parent_student', 'parents',
            'students', 'teachers', 'class_arms', 'arms', 'school_classes', 'academic_terms', 'academic_sessions',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
