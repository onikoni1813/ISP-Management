<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AccountingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@pirgacha互internet.com')
            ->orWhere('email', 'admin@pirgachainternet.com')
            ->first() ?? User::first();

        $adminId = $adminUser ? $adminUser->id : 1;

        // 1. Ensure Accounts Exist with Realistic Balances
        $accounts = [
            [
                'name' => 'Main Cash Drawer',
                'type' => 'Cash',
                'account_number' => 'CASH-MAIN',
                'balance' => 45000.00,
                'status' => 'active',
            ],
            [
                'name' => 'Islami Bank Main AC',
                'type' => 'Bank',
                'account_number' => '2050214010012345',
                'balance' => 285000.00,
                'status' => 'active',
            ],
            [
                'name' => 'bKash Merchant Account',
                'type' => 'Mobile Banking',
                'account_number' => '01711223344',
                'balance' => 84200.00,
                'status' => 'active',
            ],
            [
                'name' => 'Nagad Business AC',
                'type' => 'Mobile Banking',
                'account_number' => '01811223344',
                'balance' => 32000.00,
                'status' => 'active',
            ],
            [
                'name' => 'Brac Bank Corporate',
                'type' => 'Bank',
                'account_number' => '1501209876543210',
                'balance' => 195000.00,
                'status' => 'active',
            ],
        ];

        $createdAccounts = [];
        foreach ($accounts as $acc) {
            $createdAccounts[$acc['name']] = Account::firstOrCreate(
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

        $createdCategories = [];
        foreach ($categories as $cat) {
            $createdCategories[$cat['code']] = ExpenseCategory::firstOrCreate(
                ['code' => $cat['code']],
                ['name' => $cat['name'], 'status' => 'active']
            );
        }

        // 3. Realistic Demo Expense Items
        $demoExpenses = [
            [
                'category' => 'UPSTREAM',
                'account' => 'Islami Bank Main AC',
                'title' => 'Monthly 1.2 Gbps Upstream Bandwidth Bill to Summit Comm',
                'amount' => 45000.00,
                'days_ago' => 2,
                'description' => 'Paid via Bank Transfer for September 2026 Bandwidth allocation.',
            ],
            [
                'category' => 'FIBER_MAINT',
                'account' => 'Main Cash Drawer',
                'title' => 'Core Splicing & TJ Box replacement at Pirgacha Bazar',
                'amount' => 3800.00,
                'days_ago' => 3,
                'description' => 'Replaced damaged 24-core fiber joint after storm.',
            ],
            [
                'category' => 'RENT_UTILITIES',
                'account' => 'Brac Bank Corporate',
                'title' => 'NOC Central Server Room Monthly Rent',
                'amount' => 15000.00,
                'days_ago' => 5,
                'description' => 'Monthly rental payment to building owner for September.',
            ],
            [
                'category' => 'FUEL_GEN',
                'account' => 'Main Cash Drawer',
                'title' => 'Diesel fuel 30 Liters for Backup Generator',
                'amount' => 3450.00,
                'days_ago' => 6,
                'description' => 'Generator fuel purchased from Pirgacha Filling Station.',
            ],
            [
                'category' => 'EQUIPMENT',
                'account' => 'bKash Merchant Account',
                'title' => 'Purchased 5 units TP-Link Archer C6 Gigacore Routers',
                'amount' => 18500.00,
                'days_ago' => 7,
                'description' => 'For new corporate fiber connections.',
            ],
            [
                'category' => 'ENTERTAINMENT',
                'account' => 'Main Cash Drawer',
                'title' => 'Weekly Staff Tea, Snacks & Field Technician Lunch',
                'amount' => 1250.00,
                'days_ago' => 8,
                'description' => 'Weekly operational refreshments for support desk & linemen.',
            ],
            [
                'category' => 'FIBER_MAINT',
                'account' => 'Main Cash Drawer',
                'title' => 'Purchased 1 Drum 1000m Drop Cable & Patch Cords',
                'amount' => 7200.00,
                'days_ago' => 10,
                'description' => 'Emergency cable replenishment for College Road zone extension.',
            ],
            [
                'category' => 'RENT_UTILITIES',
                'account' => 'Nagad Business AC',
                'title' => 'NOC Room NESCO Electricity Prepaid Token Recharge',
                'amount' => 8200.00,
                'days_ago' => 12,
                'description' => 'Meter Token recharge via Nagad Business App.',
            ],
            [
                'category' => 'MARKETING',
                'account' => 'bKash Merchant Account',
                'title' => 'Printed 50 PVC Road Crossing Banners & Leaflets',
                'amount' => 4500.00,
                'days_ago' => 15,
                'description' => 'Campaign banners installed at Bazar, College Road and Rail Gate.',
            ],
            [
                'category' => 'LEGAL_BTRC',
                'account' => 'Islami Bank Main AC',
                'title' => 'Quarterly ISP License Revenue Share to BTRC',
                'amount' => 12000.00,
                'days_ago' => 18,
                'description' => 'Challan payment deposit to Bangladesh Bank treasury.',
            ],
            [
                'category' => 'EQUIPMENT',
                'account' => 'Brac Bank Corporate',
                'title' => 'Purchased 10 Units V-SOL XPON ONU (1GE+1FE+WiFi)',
                'amount' => 14000.00,
                'days_ago' => 20,
                'description' => 'Stocked in store for new home broadband installations.',
            ],
            [
                'category' => 'ENTERTAINMENT',
                'account' => 'Main Cash Drawer',
                'title' => 'Support Desk Team Dinner & Monthly Meeting',
                'amount' => 2200.00,
                'days_ago' => 25,
                'description' => 'Monthly coordination meeting dinner with field technicians.',
            ],
        ];

        foreach ($demoExpenses as $index => $item) {
            $cat = $createdCategories[$item['category']] ?? null;
            $acc = $createdAccounts[$item['account']] ?? null;

            if (!$cat || !$acc) {
                continue;
            }

            $date = Carbon::now()->subDays($item['days_ago'])->toDateString();
            $expenseNumber = 'EXP-2026-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);

            // Avoid duplicate seeding
            $expense = Expense::firstOrCreate(
                ['expense_number' => $expenseNumber],
                [
                    'expense_category_id' => $cat->id,
                    'account_id' => $acc->id,
                    'amount' => $item['amount'],
                    'expense_date' => $date,
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'paid_by' => $adminId,
                    'status' => 'posted',
                ]
            );

            // Seed ledger transaction if not already present
            AccountTransaction::firstOrCreate(
                [
                    'reference_type' => Expense::class,
                    'reference_id' => $expense->id,
                ],
                [
                    'account_id' => $acc->id,
                    'transaction_number' => 'TXN-2026-' . strtoupper(substr(md5($expenseNumber), 0, 8)),
                    'type' => 'expense',
                    'debit' => 0.00,
                    'credit' => $item['amount'],
                    'balance_after' => $acc->balance,
                    'description' => "Expense: {$expense->title}",
                    'created_by' => $adminId,
                    'created_at' => $date . ' 10:00:00',
                ]
            );
        }
    }
}
