<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Indexes that keep the busiest pages quick as the school's records grow (PostgreSQL does not index foreign keys on its own). */
    private const INDEXES = [
        ['results', ['academic_term_id', 'status'], 'results_term_status_idx'],
        ['results', ['student_id', 'academic_term_id', 'status'], 'results_student_term_status_idx'],
        ['student_fee_assignments', ['student_id', 'status'], 'fee_assignments_student_status_idx'],
        ['payment_transactions', ['student_id', 'status'], 'payment_tx_student_status_idx'],
        ['payment_transactions', ['fee_assignment_id', 'status'], 'payment_tx_assignment_status_idx'],
        ['audit_logs', ['created_at'], 'audit_logs_created_idx'],
        ['teacher_assignments', ['teacher_id', 'school_class_id'], 'teacher_assignments_teacher_class_idx'],
        ['parent_student', ['student_id'], 'parent_student_student_idx'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as [$table, $columns, $name]) {
            if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue 2;
                }
            }
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as [$table, , $name]) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
            }
        }
    }
};
