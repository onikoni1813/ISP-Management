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
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Creator (staff/admin)
            $table->enum('note_type', ['promise_to_pay', 'issue_report', 'general_remark'])->default('promise_to_pay');
            $table->text('note');
            $table->date('promise_date')->nullable(); // When customer promised to pay
            $table->decimal('promise_amount', 10, 2)->nullable();
            $table->enum('status', ['pending', 'resolved', 'cancelled'])->default('pending');
            $table->boolean('notify_admin')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
    }
};
