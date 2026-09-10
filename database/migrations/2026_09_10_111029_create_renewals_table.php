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
        Schema::create('renewals', function (Blueprint $table) {
            $table->id();
            $table->string('renewal_number')->unique(); // e.g. REN-2026-000001
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->date('previous_expiry')->nullable();
            $table->date('new_expiry');
            $table->unsignedInteger('validity_days');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->boolean('is_zero_charge')->default(false); // When true: zero extra fee charged
            $table->string('renewal_type')->default('standard'); // standard, early, expired, validity_shift, package_change
            $table->string('idempotency_key')->nullable()->unique();
            $table->foreignId('renewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('renewed_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'connection_id']);
            $table->index('renewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renewals');
    }
};
