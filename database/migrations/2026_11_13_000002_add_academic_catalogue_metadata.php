<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arms', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('subject_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 40)->unique();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('waec_subject_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_name');
            $table->string('code', 10)->unique();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::table('subjects', function (Blueprint $table): void {
            $table->foreignId('subject_category_id')->nullable()->after('name')->constrained('subject_categories')->nullOnDelete();
            $table->foreignId('waec_subject_code_id')->nullable()->after('subject_category_id')->constrained('waec_subject_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropForeign(['subject_category_id']);
            $table->dropForeign(['waec_subject_code_id']);
            $table->dropColumn(['subject_category_id', 'waec_subject_code_id']);
        });
        Schema::dropIfExists('waec_subject_codes');
        Schema::dropIfExists('subject_categories');
        Schema::table('arms', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
