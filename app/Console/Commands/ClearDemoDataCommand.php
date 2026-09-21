<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDemoDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pirgacha:clear-demo-data {--keep-users : Keep admin and staff users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all demo/sample transactional and customer records while preserving system configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database cleanup for demo data...');

        // Disable foreign keys for SQLite or MySQL
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        try {
            // 1. Clear Transactional and Customer-related Tables
            $tablesToTruncate = [
                // Billing & Payments
                'payment_allocations',
                'payments',
                'invoice_items',
                'invoices',
                'billing_cycles',
                'renewals',
                'staff_handovers',

                // Complaints
                'complaint_comments',
                'complaint_status_histories',
                'complaints',

                // Customer Notes & Core Relations
                'customer_notes',
                'customer_packages',
                'pppoe_credentials',
                'connections',
                'customer_addresses',
                'customer_contacts',
                'customers',

                // Accounting Transactions, Expenses, Payroll
                'account_transactions',
                'expenses',
                'salary_payments',
                'salary_periods',

                // SMS & Audit Logs
                'sms_logs',
                'audit_logs',
            ];

            foreach ($tablesToTruncate as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->line("<comment>Truncated table:</comment> {$table}");
                }
            }

            // 2. Reset Account balances to 0.00
            if (Schema::hasTable('accounts')) {
                DB::table('accounts')->update(['balance' => 0.00]);
                $this->line('<comment>Reset balances to 0.00 in table:</comment> accounts');
            }

            // 3. Remove Customer Users (keep Admin and Staff users)
            if (Schema::hasTable('users')) {
                // Delete role_user for non-admin and non-staff
                $adminOrStaffUserIds = DB::table('users')
                    ->join('role_user', 'users.id', '=', 'role_user.user_id')
                    ->join('roles', 'role_user.role_id', '=', 'roles.id')
                    ->whereIn('roles.slug', ['admin', 'staff'])
                    ->pluck('users.id')
                    ->unique()
                    ->toArray();

                // If admin id 1 is not captured, ensure it is preserved
                $preserveIds = array_unique(array_merge([1], $adminOrStaffUserIds));

                $deleted = DB::table('users')
                    ->whereNotIn('id', $preserveIds)
                    ->delete();

                DB::table('role_user')
                    ->whereNotIn('user_id', $preserveIds)
                    ->delete();

                $this->line("<comment>Removed {$deleted} demo customer user account(s).</comment>");
            }

            $this->info('Demo data successfully cleared!');
        } catch (\Throwable $e) {
            $this->error('Failed to clear demo data: ' . $e->getMessage());
            return 1;
        } finally {
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        return 0;
    }
}
