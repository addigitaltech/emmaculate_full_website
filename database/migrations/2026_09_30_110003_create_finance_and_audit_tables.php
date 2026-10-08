<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_fee_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_term_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount_due', 12, 2);
            $table->char('currency', 3)->default('NGN');
            $table->date('due_date')->nullable();
            $table->string('status')->default('unpaid')->index();
            $table->text('description')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'academic_session_id', 'academic_term_id'], 'sfa_student_session_term_idx');
        });

        Schema::create('payment_gateway_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('driver')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('supports_online_checkout')->default(false);
            $table->boolean('supports_refunds')->default(false);
            $table->string('display_name');
            $table->string('configuration_status')->default('not_configured');
            $table->json('public_settings')->nullable();
            // Provider secrets are intentionally environment-only and never stored here.
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fee_assignment_id')->nullable()->constrained('student_fee_assignments')->nullOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gateway')->index();
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable()->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('NGN');
            $table->string('status')->default('pending')->index();
            $table->string('checkout_url')->nullable();
            $table->json('safe_metadata')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->string('refund_status')->default('none')->index();
            $table->string('refund_reference')->nullable();
            $table->string('refund_provider_status')->nullable();
            $table->timestamp('refund_requested_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'created_at']);
        });

        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway');
            $table->string('provider_event_id')->nullable()->unique();
            $table->string('event_type');
            $table->string('payload_hash', 64);
            $table->string('processing_status')->default('received')->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['gateway', 'event_type', 'created_at']);
        });

        Schema::create('payment_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_transaction_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('receipt_number')->unique();
            $table->string('storage_path')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('safe_metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['actor_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_logs', 'payment_receipts', 'payment_events', 'payment_transactions',
            'payment_gateway_configs', 'student_fee_assignments', 'fee_types',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
