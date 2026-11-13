<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropForeign(['waec_subject_code_id']);
            $table->dropColumn('waec_subject_code_id');
        });

        Schema::dropIfExists('waec_subject_codes');
    }

    public function down(): void
    {
        Schema::create('waec_subject_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('subject_name');
            $table->string('code', 10)->unique();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::table('subjects', function (Blueprint $table): void {
            $table->foreignId('waec_subject_code_id')->nullable()->after('subject_category_id')->constrained('waec_subject_codes')->nullOnDelete();
        });
    }
};
