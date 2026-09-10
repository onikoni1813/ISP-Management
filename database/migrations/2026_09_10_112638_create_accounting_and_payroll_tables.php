<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Account Transactions Ledger (Append-only money movement)
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_number')->unique(); // TXN-2026-000001
            $table->string('type'); // customer_payment, expense, salary, transfer_in, transfer_out, refund, adjustment
            $table->decimal('debit', 12, 2)->default(0.00); // Inflow
            $table->decimal('credit', 12, 2)->default(0.00); // Outflow
            $table->decimal('balance_after', 12, 2);
            $table->string('reference_type')->nullable(); // Payment, Expense, SalaryPayment, AccountTransfer
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['account_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });

        // 2. Account Transfers (Inter-account movements e.g. Cash -> Bank)
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique(); // TRF-2026-000001
            $table->foreignId('from_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('to_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Expense Categories
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 4. Expenses Table
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique(); // EXP-2026-000001
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('posted'); // posted, void
            $table->timestamps();

            $table->index(['expense_category_id', 'expense_date']);
        });

        // 5. Salary Periods
        Schema::create('salary_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. September 2026
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('open'); // open, closed
            $table->timestamps();
        });

        // 6. Salary Payments (Period-based, preserves historical salary)
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_number')->unique(); // PAYR-2026-000001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Staff user
            $table->foreignId('salary_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('bonus', 10, 2)->default(0.00);
            $table->decimal('commission', 10, 2)->default(0.00);
            $table->decimal('advance_deduction', 10, 2)->default(0.00);
            $table->decimal('other_deductions', 10, 2)->default(0.00);
            $table->decimal('net_salary', 10, 2);
            $table->date('payment_date');
            $table->string('status')->default('paid'); // paid, void
            $table->text('notes')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'salary_period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('salary_periods');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('account_transfers');
        Schema::dropIfExists('account_transactions');
    }
};
