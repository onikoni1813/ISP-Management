<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Milestone 18 — Performance Foundation Indexes:
     * - Optimize connection queries by customer and status, expiry and status
     * - Optimize audit trail and staff accountability queries by user/module and creation timestamp
     */
    public function up(): void
    {
        // 1. Composite indexes on connections table
        Schema::table('connections', function (Blueprint $table) {
            $table->index(['customer_id', 'status'], 'connections_customer_status_idx');
            $table->index(['expiry_date', 'status'], 'connections_expiry_status_idx');
        });

        // 2. Composite indexes on audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_idx');
            $table->index(['module', 'created_at'], 'audit_logs_module_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('connections', function (Blueprint $table) {
            $table->dropIndex('connections_customer_status_idx');
            $table->dropIndex('connections_expiry_status_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_user_created_idx');
            $table->dropIndex('audit_logs_module_created_idx');
        });
    }
};
