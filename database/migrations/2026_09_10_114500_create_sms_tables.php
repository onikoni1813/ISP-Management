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
        // 1. SMS Gateways Configuration
        Schema::create('sms_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Greenweb', 'BulkSMSBD', 'AlphaSMS', 'LogDriver'
            $table->string('driver')->default('log'); // Driver key e.g., 'log', 'greenweb', 'generic_http'
            $table->text('api_url')->nullable();
            $table->text('api_key')->nullable();
            $table->string('sender_id')->nullable();
            $table->json('extra_params')->nullable(); // Additional headers or body keys
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 2. SMS Templates with Variable Tokens
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Payment Received
            $table->string('code')->unique(); // payment_received, expiry_warning, expired, renewal_completed, complaint_created, complaint_resolved, custom
            $table->text('template'); // e.g. "Dear {name}, payment of Tk {amount} received. Expiry: {expiry_date}. - Pirgacha Internet"
            $table->boolean('is_auto_enabled')->default(true);
            $table->timestamps();
        });

        // 3. SMS Delivery & Dispatch Logs
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient'); // Phone number
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('sms_templates')->nullOnDelete();
            $table->foreignId('gateway_id')->nullable()->constrained('sms_gateways')->nullOnDelete();
            $table->text('message');
            $table->string('status')->default('sent'); // queued, sent, failed
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type')->nullable(); // Payment, Renewal, Complaint
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->timestamps();

            $table->index(['recipient', 'status']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('sms_gateways');
    }
};
