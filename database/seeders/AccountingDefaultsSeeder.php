<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class AccountingDefaultsSeeder extends Seeder
{
    /**
     * Seed production default accounts & expense categories without demo expenses.
     */
    public function run(): void
    {
        // 1. Production Default Accounts
        $accounts = [
            [
                'name' => 'Main Cash Drawer',
                'type' => 'Cash',
                'account_number' => 'CASH-MAIN',
                'balance' => 0.00,
                'status' => 'active',
            ],
            [
                'name' => 'Islami Bank Main AC',
                'type' => 'Bank',
                'account_number' => '2050214010012345',
                'balance' => 0.00,
                'status' => 'active',
            ],
            [
                'name' => 'bKash Merchant Account',
                'type' => 'Mobile Banking',
                'account_number' => '01711223344',
                'balance' => 0.00,
                'status' => 'active',
            ],
            [
                'name' => 'Nagad Business AC',
                'type' => 'Mobile Banking',
                'account_number' => '01811223344',
                'balance' => 0.00,
                'status' => 'active',
            ],
            [
                'name' => 'Brac Bank Corporate',
                'type' => 'Bank',
                'account_number' => '1501209876543210',
                'balance' => 0.00,
                'status' => 'active',
            ],
        ];

        foreach ($accounts as $acc) {
            Account::firstOrCreate(
                ['account_number' => $acc['account_number']],
                $acc
            );
        }

        // 2. Realistic ISP Expense Categories
        $categories = [
            ['name' => 'Bandwidth & Upstream (IIG/ITC)', 'code' => 'UPSTREAM'],
            ['name' => 'Optical Fiber & Network Maintenance', 'code' => 'FIBER_MAINT'],
            ['name' => 'Office Rent & Utilities', 'code' => 'RENT_UTILITIES'],
            ['name' => 'Staff Food & Entertainment', 'code' => 'ENTERTAINMENT'],
            ['name' => 'Generator & Electricity Fuel', 'code' => 'FUEL_GEN'],
            ['name' => 'Equipment & Hardware Purchases', 'code' => 'EQUIPMENT'],
            ['name' => 'Marketing & Promotional Banners', 'code' => 'MARKETING'],
            ['name' => 'Govt License & BTRC Fees', 'code' => 'LEGAL_BTRC'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(
                ['code' => $cat['code']],
                ['name' => $cat['name'], 'status' => 'active']
            );
        }
    }
}
