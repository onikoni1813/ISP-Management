<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\BillingCycle;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\ComplaintStatusHistory;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerPackage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PppoeCredential;
use App\Models\Renewal;
use App\Models\SalaryPeriod;
use App\Models\SalaryPayment;
use App\Models\SmsGateway;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComprehensiveDemoSeeder extends Seeder
{
    /**
     * Seed comprehensive, realistic demo data across all modules:
     * - Staff & Customers
     * - Fiber Coverage Areas
     * - Packages
     * - Connections & Encrypted PPPoE
     * - Billing Cycles & Invoices (Paid, Unpaid, Partially Paid, Overdue)
     * - Payments with Ledger Transactions & Allocations
     * - Renewals History
     * - Support Complaints, Status History & Internal Comments
     * - SMS Logs & Templates
     * - Payroll & Salary Disbursals
     * - Audit Trail Records
     */
    public function run(): void
    {
        // 1. Ensure Defaults Seeded
        $this->call([
            RolesAndPermissionsSeeder::class,
            CoreIspSeeder::class,
            AccountingDemoSeeder::class,
            SmsDefaultsSeeder::class,
            CmsFaqSeeder::class,
        ]);

        $admin = User::where('email', 'admin@pirgachainternet.com')->first();
        $staff1 = User::where('email', 'staff@pirgachainternet.com')->first();

        // Additional Staff Members for rich multi-staff testing
        $staff2 = User::firstOrCreate(
            ['email' => 'tanvir.noc@pirgachainternet.com'],
            [
                'name' => 'Tanvir Ahmed (NOC Eng.)',
                'phone' => '01744112233',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $staff2->assignRole('staff');

        $staff3 = User::firstOrCreate(
            ['email' => 'sujan.field@pirgachainternet.com'],
            [
                'name' => 'Sujan Mia (Lineman)',
                'phone' => '01755223344',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $staff3->assignRole('staff');

        $staffList = [$staff1, $staff2, $staff3];

        // 2. Expand Fiber Coverage Areas
        $mainArea = Area::firstOrCreate(
            ['code' => 'AREA-PIRGACHA'],
            ['name' => 'Pirgacha Sadar', 'status' => 'active']
        );

        $areasData = [
            ['code' => 'ZONE-BAZAR', 'name' => 'Pirgacha Bazar', 'desc' => 'Commercial center & market zone'],
            ['code' => 'ZONE-COLLEGE', 'name' => 'College Road', 'desc' => 'Residential & student zone'],
            ['code' => 'ZONE-STATION', 'name' => 'Rail Station Area', 'desc' => 'Market & railway residential zone'],
            ['code' => 'ZONE-UPAZILA', 'name' => 'Upazila Parishad Gate', 'desc' => 'Government offices & residential'],
            ['code' => 'ZONE-HAAT', 'name' => 'Purba Haat Toli', 'desc' => 'Dense residential neighborhood'],
            ['code' => 'ZONE-DEUTI', 'name' => 'Deuti Road Junction', 'desc' => 'Semi-urban expansion line'],
        ];

        $areas = [];
        foreach ($areasData as $a) {
            $areas[$a['code']] = Area::firstOrCreate(
                ['code' => $a['code']],
                [
                    'parent_id' => $mainArea->id,
                    'name' => $a['name'],
                    'status' => 'active',
                    'description' => $a['desc'],
                ]
            );
        }

        // 3. Ensure Packages Exist
        $packages = Package::all()->keyBy('code');

        // Accounts
        $cashAccount = Account::where('name', 'Main Cash Drawer')->first() ?? Account::first();
        $bkashAccount = Account::where('name', 'bKash Merchant Account')->first() ?? Account::first();
        $nagadAccount = Account::where('name', 'Nagad Business AC')->first() ?? Account::first();
        $bankAccount = Account::where('name', 'Islami Bank Main AC')->first() ?? Account::first();
        $allAccounts = [$cashAccount, $bkashAccount, $nagadAccount, $bankAccount];

        // 4. Create Current & Previous Month Billing Cycles
        $prevMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $prevCycle = BillingCycle::firstOrCreate(
            ['name' => $prevMonthStart->format('F Y')],
            [
                'start_date' => $prevMonthStart->toDateString(),
                'end_date' => $prevMonthEnd->toDateString(),
                'due_date' => $prevMonthStart->copy()->addDays(10)->toDateString(),
                'status' => 'closed',
            ]
        );

        $currMonthStart = Carbon::now()->startOfMonth();
        $currMonthEnd = Carbon::now()->endOfMonth();
        $currCycle = BillingCycle::firstOrCreate(
            ['name' => $currMonthStart->format('F Y')],
            [
                'start_date' => $currMonthStart->toDateString(),
                'end_date' => $currMonthEnd->toDateString(),
                'due_date' => $currMonthStart->copy()->addDays(10)->toDateString(),
                'status' => 'active',
            ]
        );

        // 5. Generate 25 Realistic ISP Customers Across Various Zones & Packages
        $customerSeedList = [
            ['name' => 'Rafiqul Islam', 'phone' => '01711100001', 'area' => 'ZONE-BAZAR', 'pkg' => 'PKG-15M', 'type' => 'paid_regular'],
            ['name' => 'Md. Asaduzzaman', 'phone' => '01711100002', 'area' => 'ZONE-COLLEGE', 'pkg' => 'PKG-10M', 'type' => 'paid_regular'],
            ['name' => 'Nazmul Huda', 'phone' => '01711100003', 'area' => 'ZONE-STATION', 'pkg' => 'PKG-20M', 'type' => 'due_pending'],
            ['name' => 'Farhana Akter', 'phone' => '01711100004', 'area' => 'ZONE-UPAZILA', 'pkg' => 'PKG-30M', 'type' => 'paid_regular'],
            ['name' => 'Dr. Shamsul Alam', 'phone' => '01711100005', 'area' => 'ZONE-BAZAR', 'pkg' => 'PKG-30M', 'type' => 'paid_regular'],
            ['name' => 'Shahidul Alam', 'phone' => '01711100006', 'area' => 'ZONE-HAAT', 'pkg' => 'PKG-10M', 'type' => 'due_unpaid'],
            ['name' => 'Kamrul Hasan', 'phone' => '01711100007', 'area' => 'ZONE-DEUTI', 'pkg' => 'PKG-15M', 'type' => 'due_partial'],
            ['name' => 'Mizanur Rahman', 'phone' => '01711100008', 'area' => 'ZONE-COLLEGE', 'pkg' => 'PKG-20M', 'type' => 'paid_today'],
            ['name' => 'Al-Amin Hossain', 'phone' => '01711100009', 'area' => 'ZONE-BAZAR', 'pkg' => 'PKG-10M', 'type' => 'paid_today'],
            ['name' => 'Jannatul Ferdous', 'phone' => '01711100010', 'area' => 'ZONE-UPAZILA', 'pkg' => 'PKG-15M', 'type' => 'expired'],
            ['name' => 'Tariqul Islam', 'phone' => '01711100011', 'area' => 'ZONE-STATION', 'pkg' => 'PKG-10M', 'type' => 'paid_regular'],
            ['name' => 'Hasan Mahmud', 'phone' => '01711100012', 'area' => 'ZONE-HAAT', 'pkg' => 'PKG-20M', 'type' => 'due_unpaid'],
            ['name' => 'Golam Kibria', 'phone' => '01711100013', 'area' => 'ZONE-COLLEGE', 'pkg' => 'PKG-15M', 'type' => 'paid_regular'],
            ['name' => 'Mahabub Alam', 'phone' => '01711100014', 'area' => 'ZONE-BAZAR', 'pkg' => 'PKG-30M', 'type' => 'paid_today'],
            ['name' => 'Sumon Chandra Roy', 'phone' => '01711100015', 'area' => 'ZONE-DEUTI', 'pkg' => 'PKG-10M', 'type' => 'due_pending'],
            ['name' => 'Anwar Hossain', 'phone' => '01711100016', 'area' => 'ZONE-UPAZILA', 'pkg' => 'PKG-15M', 'type' => 'paid_regular'],
            ['name' => 'Rubel Sarkar', 'phone' => '01711100017', 'area' => 'ZONE-STATION', 'pkg' => 'PKG-10M', 'type' => 'complaint_open'],
            ['name' => 'Shah Newaz', 'phone' => '01711100018', 'area' => 'ZONE-HAAT', 'pkg' => 'PKG-20M', 'type' => 'complaint_urgent'],
            ['name' => 'Abdur Rahim Bepari', 'phone' => '01711100019', 'area' => 'ZONE-BAZAR', 'pkg' => 'PKG-30M', 'type' => 'paid_regular'],
            ['name' => 'Nasima Begum', 'phone' => '01711100020', 'area' => 'ZONE-COLLEGE', 'pkg' => 'PKG-10M', 'type' => 'paid_regular'],
            ['name' => 'Zakir Hossain', 'phone' => '01711100021', 'area' => 'ZONE-DEUTI', 'pkg' => 'PKG-15M', 'type' => 'due_unpaid'],
            ['name' => 'Mostafizur Rahman', 'phone' => '01711100022', 'area' => 'ZONE-STATION', 'pkg' => 'PKG-20M', 'type' => 'complaint_resolved'],
            ['name' => 'Sharmin Akter', 'phone' => '01711100023', 'area' => 'ZONE-UPAZILA', 'pkg' => 'PKG-10M', 'type' => 'paid_regular'],
            ['name' => 'Sadek Ali', 'phone' => '01711100024', 'area' => 'ZONE-HAAT', 'pkg' => 'PKG-15M', 'type' => 'expired'],
            ['name' => 'Monirul Islam', 'phone' => '01711100025', 'area' => 'ZONE-BAZAR', 'pkg' => 'PKG-20M', 'type' => 'paid_today'],
        ];

        $today = Carbon::today();
        $gateways = SmsGateway::all();
        $activeGateway = $gateways->first();
        $paymentTemplate = SmsTemplate::where('code', 'payment_received')->first();

        foreach ($customerSeedList as $idx => $cData) {
            $num = $idx + 2; // CUST-000002 onwards
            $code = 'CUST-' . str_pad((string) $num, 6, '0', STR_PAD_LEFT);
            $conCode = 'CON-' . str_pad((string) $num, 6, '0', STR_PAD_LEFT);
            $targetArea = $areas[$cData['area']] ?? $mainArea;
            $pkg = $packages[$cData['pkg']] ?? $packages->first();
            $price = $pkg->currentPrice?->price ?? 700.00;

            // Status & Dates based on customer type
            $isExpired = in_array($cData['type'], ['expired']);
            $connStatus = $isExpired ? 'expired' : 'active';
            $custStatus = $isExpired ? 'inactive' : 'active';

            $expiryDate = $isExpired
                ? $today->copy()->subDays(rand(2, 10))->toDateString()
                : $today->copy()->addDays(rand(12, 35))->toDateString();

            // Create or update customer
            $customer = Customer::firstOrCreate(
                ['customer_code' => $code],
                [
                    'area_id' => $targetArea->id,
                    'name' => $cData['name'],
                    'status' => $custStatus,
                    'join_date' => Carbon::now()->subMonths(rand(2, 12))->toDateString(),
                    'billing_day' => rand(1, 5),
                    'balance' => 0.00,
                    'created_by' => $admin->id,
                    'notes' => "Broadband optical fiber customer at {$targetArea->name}",
                ]
            );

            // Primary Contact
            CustomerContact::firstOrCreate(
                ['customer_id' => $customer->id, 'contact_type' => 'primary'],
                [
                    'name' => $cData['name'],
                    'phone' => $cData['phone'],
                    'email' => Str::slug($cData['name']) . "{$num}@pirgachainternet.com",
                ]
            );

            // Installation Address
            CustomerAddress::firstOrCreate(
                ['customer_id' => $customer->id, 'address_type' => 'installation'],
                [
                    'house_no' => 'H-' . rand(1, 99),
                    'road_no' => 'R-' . rand(1, 15),
                    'village_or_area' => $targetArea->name,
                    'post_office' => 'Pirgacha',
                    'full_address' => "Holding # " . rand(10, 200) . ", {$targetArea->name}, Pirgacha, Rangpur",
                ]
            );

            // Connection & PPPoE Credentials
            $connection = Connection::firstOrCreate(
                ['connection_code' => $conCode],
                [
                    'customer_id' => $customer->id,
                    'area_id' => $targetArea->id,
                    'current_package_id' => $pkg->id,
                    'protocol' => 'pppoe',
                    'ip_address' => '10.20.' . rand(1, 50) . '.' . rand(2, 250),
                    'mac_address' => sprintf('%02X:%02X:%02X:%02X:%02X:%02X', rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255)),
                    'router_model' => (rand(0, 1) === 1 ? 'TP-Link Archer C6' : 'Netis WF2409E'),
                    'fiber_box_id' => 'TJ-' . $targetArea->code . '-BOX' . rand(1, 5),
                    'status' => $connStatus,
                    'installation_date' => Carbon::now()->subMonths(4)->toDateString(),
                    'expiry_date' => $expiryDate,
                ]
            );

            PppoeCredential::firstOrCreate(
                ['connection_id' => $connection->id],
                [
                    'username' => strtolower(Str::slug($cData['name'], '_')) . "_{$num}",
                    'password' => 'secret_pass_' . rand(1000, 9999),
                    'service_name' => 'pirgacha_pppoe',
                    'status' => $connStatus,
                ]
            );

            CustomerPackage::firstOrCreate(
                ['customer_id' => $customer->id, 'connection_id' => $connection->id],
                [
                    'package_id' => $pkg->id,
                    'actual_price' => $price,
                    'start_date' => Carbon::now()->subMonths(3)->toDateString(),
                    'assigned_by' => $admin->id,
                    'status' => 'active',
                    'remarks' => "Standard subscription {$pkg->name}",
                ]
            );

            // 6. Invoices, Payments & Balance Setup based on Customer Type
            $invNumber = 'INV-' . $currMonthStart->format('Ym') . '-' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
            $staffAssigned = $staffList[$idx % count($staffList)];
            $accountChosen = $allAccounts[$idx % count($allAccounts)];

            if ($cData['type'] === 'paid_regular') {
                // Paid earlier this month
                $inv = Invoice::firstOrCreate(
                    ['invoice_number' => $invNumber],
                    [
                        'customer_id' => $customer->id,
                        'billing_cycle_id' => $currCycle->id,
                        'period_start' => $currMonthStart->toDateString(),
                        'period_end' => $currMonthEnd->toDateString(),
                        'due_date' => $currMonthStart->copy()->addDays(10)->toDateString(),
                        'subtotal' => $price,
                        'discount' => 0.00,
                        'tax' => 0.00,
                        'total' => $price,
                        'paid_amount' => $price,
                        'due_amount' => 0.00,
                        'status' => 'paid',
                        'created_by' => $admin->id,
                        'notes' => 'Monthly internet service bill',
                    ]
                );

                InvoiceItem::firstOrCreate(
                    ['invoice_id' => $inv->id],
                    [
                        'item_type' => 'package',
                        'description' => "Monthly {$pkg->name} ({$pkg->speed_mbps} Mbps)",
                        'unit_price' => $price,
                        'quantity' => 1,
                        'total' => $price,
                    ]
                );

                $payNumber = 'PAY-' . Carbon::now()->format('Ym') . '-' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
                $payment = Payment::firstOrCreate(
                    ['payment_number' => $payNumber],
                    [
                        'customer_id' => $customer->id,
                        'account_id' => $accountChosen->id,
                        'amount' => $price,
                        'payment_method' => (rand(0, 1) === 1 ? 'cash' : 'bkash'),
                        'reference' => 'TXN' . strtoupper(Str::random(8)),
                        'idempotency_key' => Str::uuid()->toString(),
                        'paid_at' => Carbon::now()->subDays(rand(3, 8))->toDateTimeString(),
                        'collected_by' => $staffAssigned->id,
                        'status' => 'completed',
                        'notes' => 'Monthly bill collection',
                    ]
                );

                PaymentAllocation::firstOrCreate(
                    ['payment_id' => $payment->id, 'invoice_id' => $inv->id],
                    ['allocated_amount' => $price]
                );

                // Renewal History
                Renewal::firstOrCreate(
                    ['customer_id' => $customer->id, 'connection_id' => $connection->id, 'renewal_number' => 'RNW-' . str_pad((string) $num, 6, '0', STR_PAD_LEFT)],
                    [
                        'package_id' => $pkg->id,
                        'invoice_id' => $inv->id,
                        'payment_id' => $payment->id,
                        'previous_expiry' => $currMonthStart->toDateString(),
                        'new_expiry' => $expiryDate,
                        'validity_days' => 30,
                        'amount' => $price,
                        'is_zero_charge' => false,
                        'renewal_type' => 'paid',
                        'idempotency_key' => Str::uuid()->toString(),
                        'renewed_by' => $staffAssigned->id,
                        'renewed_at' => Carbon::now()->subDays(rand(3, 8))->toDateTimeString(),
                        'notes' => 'Auto-renewal on full payment',
                    ]
                );

            } elseif ($cData['type'] === 'paid_today') {
                // Paid TODAY! (Affects Today's Collection on Dashboard)
                $inv = Invoice::firstOrCreate(
                    ['invoice_number' => $invNumber],
                    [
                        'customer_id' => $customer->id,
                        'billing_cycle_id' => $currCycle->id,
                        'period_start' => $currMonthStart->toDateString(),
                        'period_end' => $currMonthEnd->toDateString(),
                        'due_date' => $currMonthStart->copy()->addDays(10)->toDateString(),
                        'subtotal' => $price,
                        'discount' => 0.00,
                        'tax' => 0.00,
                        'total' => $price,
                        'paid_amount' => $price,
                        'due_amount' => 0.00,
                        'status' => 'paid',
                        'created_by' => $admin->id,
                        'notes' => 'Monthly internet bill - Collected Today',
                    ]
                );

                InvoiceItem::firstOrCreate(
                    ['invoice_id' => $inv->id],
                    [
                        'item_type' => 'package',
                        'description' => "Monthly {$pkg->name} ({$pkg->speed_mbps} Mbps)",
                        'unit_price' => $price,
                        'quantity' => 1,
                        'total' => $price,
                    ]
                );

                $payNumber = 'PAY-' . Carbon::now()->format('Ym') . '-TODAY-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
                $payment = Payment::firstOrCreate(
                    ['payment_number' => $payNumber],
                    [
                        'customer_id' => $customer->id,
                        'account_id' => $accountChosen->id,
                        'amount' => $price,
                        'payment_method' => 'cash',
                        'reference' => 'CASH-REC-' . rand(10000, 99999),
                        'idempotency_key' => Str::uuid()->toString(),
                        'paid_at' => Carbon::now()->subHours(rand(1, 5))->toDateTimeString(),
                        'collected_by' => $staffAssigned->id,
                        'status' => 'completed',
                        'notes' => 'Field cash collection today',
                    ]
                );

                PaymentAllocation::firstOrCreate(
                    ['payment_id' => $payment->id, 'invoice_id' => $inv->id],
                    ['allocated_amount' => $price]
                );

                // Add to Account Ledger
                AccountTransaction::firstOrCreate(
                    [
                        'reference_type' => Payment::class,
                        'reference_id' => $payment->id,
                    ],
                    [
                        'account_id' => $accountChosen->id,
                        'transaction_number' => 'TXN-PAY-' . rand(100000, 999999),
                        'type' => 'payment',
                        'debit' => $price,
                        'credit' => 0.00,
                        'balance_after' => $accountChosen->balance + $price,
                        'description' => "Customer Collection: {$customer->name} ({$customer->customer_code})",
                        'created_by' => $staffAssigned->id,
                        'created_at' => Carbon::now()->subHours(rand(1, 5))->toDateTimeString(),
                    ]
                );

                // Log SMS Sent
                SmsLog::create([
                    'recipient' => $cData['phone'],
                    'customer_id' => $customer->id,
                    'template_id' => $paymentTemplate?->id,
                    'gateway_id' => $activeGateway?->id,
                    'message' => "Dear {$customer->name}, received Tk {$price} for your bill. Expiry: {$expiryDate}. Thank you, Pirgacha Internet.",
                    'status' => 'delivered',
                    'provider_message_id' => 'MSG-' . Str::random(12),
                    'sent_at' => Carbon::now()->subHours(rand(1, 4)),
                    'sent_by' => $staffAssigned->id,
                    'entity_type' => Payment::class,
                    'entity_id' => $payment->id,
                ]);

            } elseif ($cData['type'] === 'due_partial') {
                // Partially Paid Invoice
                $paidPortion = 300.00;
                $duePortion = $price - $paidPortion;

                $inv = Invoice::firstOrCreate(
                    ['invoice_number' => $invNumber],
                    [
                        'customer_id' => $customer->id,
                        'billing_cycle_id' => $currCycle->id,
                        'period_start' => $currMonthStart->toDateString(),
                        'period_end' => $currMonthEnd->toDateString(),
                        'due_date' => $currMonthStart->copy()->addDays(10)->toDateString(),
                        'subtotal' => $price,
                        'discount' => 0.00,
                        'tax' => 0.00,
                        'total' => $price,
                        'paid_amount' => $paidPortion,
                        'due_amount' => $duePortion,
                        'status' => 'partially_paid',
                        'created_by' => $admin->id,
                        'notes' => 'Partial payment made',
                    ]
                );

                InvoiceItem::firstOrCreate(
                    ['invoice_id' => $inv->id],
                    [
                        'item_type' => 'package',
                        'description' => "Monthly {$pkg->name} ({$pkg->speed_mbps} Mbps)",
                        'unit_price' => $price,
                        'quantity' => 1,
                        'total' => $price,
                    ]
                );

                $customer->update(['balance' => -$duePortion]);

            } elseif (in_array($cData['type'], ['due_unpaid', 'due_pending', 'expired'])) {
                // Entirely unpaid invoice
                $inv = Invoice::firstOrCreate(
                    ['invoice_number' => $invNumber],
                    [
                        'customer_id' => $customer->id,
                        'billing_cycle_id' => $currCycle->id,
                        'period_start' => $currMonthStart->toDateString(),
                        'period_end' => $currMonthEnd->toDateString(),
                        'due_date' => $currMonthStart->copy()->addDays(10)->toDateString(),
                        'subtotal' => $price,
                        'discount' => 0.00,
                        'tax' => 0.00,
                        'total' => $price,
                        'paid_amount' => 0.00,
                        'due_amount' => $price,
                        'status' => 'unpaid',
                        'created_by' => $admin->id,
                        'notes' => 'Current bill pending collection',
                    ]
                );

                InvoiceItem::firstOrCreate(
                    ['invoice_id' => $inv->id],
                    [
                        'item_type' => 'package',
                        'description' => "Monthly {$pkg->name} ({$pkg->speed_mbps} Mbps)",
                        'unit_price' => $price,
                        'quantity' => 1,
                        'total' => $price,
                    ]
                );

                $customer->update(['balance' => -$price]);
            }

            // 7. Seed Real Complaints for Support Testing
            if ($cData['type'] === 'complaint_open') {
                $comp = Complaint::firstOrCreate(
                    ['complaint_number' => 'TKT-2026-0001'],
                    [
                        'customer_id' => $customer->id,
                        'connection_id' => $connection->id,
                        'assigned_to' => $staff1->id,
                        'subject' => 'Slow internet browsing and high ping during evening',
                        'description' => 'Customer reports YouTube buffering and speed drop from 8 PM to 11 PM.',
                        'priority' => 'medium',
                        'status' => 'in_progress',
                        'created_by' => $admin->id,
                        'created_at' => Carbon::now()->subHours(18),
                    ]
                );

                ComplaintComment::firstOrCreate(
                    ['complaint_id' => $comp->id, 'comment' => 'Optical line attenuation checked (-19 dBm, good). Inspecting upstream bandwidth utilization.'],
                    ['user_id' => $staff1->id, 'is_internal' => true]
                );

                ComplaintStatusHistory::firstOrCreate(
                    ['complaint_id' => $comp->id, 'new_status' => 'in_progress'],
                    ['old_status' => 'open', 'changed_by' => $staff1->id, 'note' => 'Technician assigned and dispatched for optical inspection.']
                );
            }

            if ($cData['type'] === 'complaint_urgent') {
                $comp = Complaint::firstOrCreate(
                    ['complaint_number' => 'TKT-2026-0002'],
                    [
                        'customer_id' => $customer->id,
                        'connection_id' => $connection->id,
                        'assigned_to' => $staff3->id,
                        'subject' => 'LOS Red Light blinking on ONU (Total Optical Fiber Cut)',
                        'description' => 'Drop fiber snapped near college gate road crossing due to heavy truck movement.',
                        'priority' => 'urgent',
                        'status' => 'assigned',
                        'created_by' => $admin->id,
                        'created_at' => Carbon::now()->subHours(2),
                    ]
                );

                ComplaintComment::firstOrCreate(
                    ['complaint_id' => $comp->id, 'comment' => 'Lineman Sujan on site with fiber splicing machine.'],
                    ['user_id' => $staff3->id, 'is_internal' => true]
                );
            }

            if ($cData['type'] === 'complaint_resolved') {
                $comp = Complaint::firstOrCreate(
                    ['complaint_number' => 'TKT-2026-0003'],
                    [
                        'customer_id' => $customer->id,
                        'connection_id' => $connection->id,
                        'assigned_to' => $staff2->id,
                        'subject' => 'WiFi router password reset requested',
                        'description' => 'Customer forgot WiFi security key after router power flicker.',
                        'priority' => 'low',
                        'status' => 'resolved',
                        'created_by' => $admin->id,
                        'resolved_by' => $staff2->id,
                        'resolved_at' => Carbon::now()->subHours(5),
                        'resolution_note' => 'Connected remotely and reconfigured SSID with secure WPA2 passkey. Verified speed.',
                        'created_at' => Carbon::now()->subHours(8),
                    ]
                );

                ComplaintStatusHistory::firstOrCreate(
                    ['complaint_id' => $comp->id, 'new_status' => 'resolved'],
                    ['old_status' => 'in_progress', 'changed_by' => $staff2->id, 'note' => 'Resolved and confirmed working with customer over phone.']
                );
            }

            // 8. Audit Trail Sample Entries
            AuditLog::create([
                'user_id' => $staffAssigned->id,
                'action' => 'customer.view',
                'module' => 'customers',
                'entity_type' => Customer::class,
                'entity_id' => $customer->id,
                'old_values' => null,
                'new_values' => ['name' => $customer->name, 'code' => $customer->customer_code],
                'ip_address' => '103.145.118.' . rand(10, 200),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0',
                'created_at' => Carbon::now()->subDays(rand(1, 10))->subHours(rand(1, 12)),
            ]);
        }

        // 9. Seed Payroll Periods & Disbursal Records
        $lastMonthPeriod = SalaryPeriod::firstOrCreate(
            ['name' => Carbon::now()->subMonth()->format('F Y')],
            [
                'start_date' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->subMonth()->endOfMonth()->toDateString(),
                'status' => 'paid',
            ]
        );

        foreach ($staffList as $stIndex => $staffMember) {
            $basic = 15000.00 + ($stIndex * 2500);
            $bonus = 1000.00;
            $net = $basic + $bonus;

            SalaryPayment::firstOrCreate(
                [
                    'user_id' => $staffMember->id,
                    'salary_period_id' => $lastMonthPeriod->id,
                ],
                [
                    'payroll_number' => 'PAYROLL-' . Carbon::now()->subMonth()->format('Ym') . '-' . str_pad((string) $staffMember->id, 3, '0', STR_PAD_LEFT),
                    'account_id' => $bankAccount->id,
                    'basic_salary' => $basic,
                    'bonus' => $bonus,
                    'commission' => 0.00,
                    'advance_deduction' => 0.00,
                    'other_deductions' => 0.00,
                    'net_salary' => $net,
                    'payment_date' => Carbon::now()->subDays(5)->toDateString(),
                    'status' => 'paid',
                    'notes' => "Monthly salary disbursed via bank transfer to {$staffMember->name}",
                    'paid_by' => $admin->id,
                ]
            );
        }
    }
}
