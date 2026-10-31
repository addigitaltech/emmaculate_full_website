<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How much of each fee has been paid (needed for installments).
        Schema::table('student_fee_assignments', function (Blueprint $table): void {
            $table->decimal('amount_paid', 12, 2)->default(0);
        });
        DB::table('student_fee_assignments')->where('status', 'paid')->update(['amount_paid' => DB::raw('amount_due')]);

        // One payment can cover several fees (full payment) or part of one fee (installment).
        Schema::create('payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_assignment_id')->constrained('student_fee_assignments')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['payment_transaction_id', 'fee_assignment_id'], 'payment_allocation_unique');
            $table->index('fee_assignment_id', 'payment_allocation_fee_idx');
        });

        Schema::table('payment_transactions', function (Blueprint $table): void {
            // Set once the school and the payer have been told, so nobody is notified twice.
            $table->timestamp('notified_at')->nullable();
        });

        Schema::table('school_settings', function (Blueprint $table): void {
            $table->string('payment_mode', 12)->default('installment');          // installment | full
            $table->decimal('installment_min_amount', 12, 2)->default(1000);
            $table->text('finance_notify_emails')->nullable();                    // comma-separated; empty = school email
            $table->text('finance_notify_phones')->nullable();                    // comma-separated; empty = school phone
            $table->boolean('sms_payer')->default(false);                         // also text the payer a confirmation
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->dropColumn(['payment_mode', 'installment_min_amount', 'finance_notify_emails', 'finance_notify_phones', 'sms_payer']);
        });
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropColumn('notified_at');
        });
        Schema::dropIfExists('payment_allocations');
        Schema::table('student_fee_assignments', function (Blueprint $table): void {
            $table->dropColumn('amount_paid');
        });
    }
};
