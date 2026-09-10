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
        // 1. Accounts (Cash, Bank, bKash, Nagad)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Cash in Hand, bKash Merchant, Bank
            $table->string('type')->default('cash'); // cash, bank, mobile_wallet
            $table->string('account_number')->nullable();
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 2. Billing Cycles
        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. September 2026
            $table->date('start_date');
            $table->date('end_date');
            $table->date('due_date');
            $table->string('status')->default('active'); // active, closed
            $table->timestamps();
        });

        // 3. Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // e.g. INV-2026-000001
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('tax', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('due_amount', 10, 2)->default(0.00);
            $table->string('status')->default('unpaid'); // unpaid, partial, paid, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('due_date');
        });

        // 4. Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('item_type')->default('package'); // package, previous_due, installation, equipment, other
            $table->string('description');
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        // 5. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique(); // e.g. PAY-2026-000001
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->default('cash'); // cash, bkash, nagad, bank, other
            $table->string('reference')->nullable();
            $table->string('idempotency_key')->nullable()->unique(); // Prevents duplicate submissions
            $table->dateTime('paid_at');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('completed'); // completed, reversed, refunded, void
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'paid_at']);
        });

        // 6. Payment Allocations (Supports Partial & Multi-Invoice payments)
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('allocated_amount', 10, 2);
            $table->timestamps();

            $table->index(['payment_id', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('billing_cycles');
        Schema::dropIfExists('accounts');
    }
};
