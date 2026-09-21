<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BillingCycle;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CustomerPackage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PppoeCredential;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoCustomerSixMonthsHistorySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@pirgachainternet.com')->first() ?? User::first();
        $account = Account::first();

        // 1. Ensure or find our demo test customer
        $customer = Customer::with(['connections.pppoeCredential', 'primaryContact'])->first();

        if (!$customer) {
            $this->command->error("No customer found to add history.");
            return;
        }

        $connection = $customer->connections->first();
        $pppoe = $connection?->pppoeCredential;

        // Set easy credentials for testing
        $username = 'demo_user';
        $password = '123456';

        if (!$pppoe) {
            $pppoe = PppoeCredential::create([
                'connection_id' => $connection->id,
                'username' => $username,
                'password' => $password,
                'service_name' => 'pirgacha_pppoe',
                'status' => 'active',
            ]);
        } else {
            $pppoe->username = $username;
            $pppoe->password = $password;
            $pppoe->status = 'active';
            $pppoe->save();
        }

        // Ensure customer linked User exists with exact credentials
        $user = null;
        if ($customer->user_id) {
            $user = User::find($customer->user_id);
        }

        if (!$user) {
            $user = User::where('username', $username)
                ->orWhere('email', 'demo@pirgacha.net')
                ->orWhere('phone', $customer->primaryContact?->phone)
                ->first();
        }

        if (!$user) {
            $user = User::create([
                'name' => $customer->name,
                'email' => 'demo@pirgacha.net',
                'username' => $username,
                'phone' => $customer->primaryContact?->phone ?? '01700000000',
                'password' => Hash::make($password),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        } else {
            $user->username = $username;
            $user->password = Hash::make($password);
            $user->status = 'active';
            $user->save();
        }

        if (!$user->hasRole('customer')) {
            $user->assignRole('customer');
        }

        $customer->update(['user_id' => $user->id]);

        // Monthly bill amount
        $monthlyBill = 800.00;

        // 2. Generate 6 consecutive months of Invoices & Payments (Past 6 months: March 2026 to August/September 2026)
        $months = [
            ['offset' => 6, 'status' => 'paid'],
            ['offset' => 5, 'status' => 'paid'],
            ['offset' => 4, 'status' => 'paid'],
            ['offset' => 3, 'status' => 'paid'],
            ['offset' => 2, 'status' => 'paid'],
            ['offset' => 1, 'status' => 'paid'],
            ['offset' => 0, 'status' => 'paid'], // Current month
        ];

        foreach ($months as $m) {
            $cycleDate = Carbon::now()->subMonths($m['offset']);
            $periodStart = $cycleDate->copy()->startOfMonth()->toDateString();
            $periodEnd = $cycleDate->copy()->endOfMonth()->toDateString();
            $dueDate = $cycleDate->copy()->startOfMonth()->addDays(10)->toDateString();
            $paymentDate = $cycleDate->copy()->startOfMonth()->addDays(rand(3, 8))->setTime(rand(10, 18), rand(10, 50));

            $cycleName = $cycleDate->format('F Y');
            $billingCycle = BillingCycle::firstOrCreate(
                ['name' => $cycleName],
                [
                    'start_date' => $periodStart,
                    'end_date' => $periodEnd,
                    'due_date' => $dueDate,
                    'status' => ($m['offset'] === 0 ? 'active' : 'closed'),
                ]
            );

            // Generate unique invoice number
            $invNumber = 'INV-' . $cycleDate->format('Ym') . '-' . str_pad((string)$customer->id, 5, '0', STR_PAD_LEFT);

            $invoice = Invoice::firstOrCreate(
                ['invoice_number' => $invNumber],
                [
                    'billing_cycle_id' => $billingCycle->id,
                    'customer_id' => $customer->id,
                    'subtotal' => $monthlyBill,
                    'discount' => 0.00,
                    'tax' => 0.00,
                    'total' => $monthlyBill,
                    'paid_amount' => $monthlyBill,
                    'due_amount' => 0.00,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'due_date' => $dueDate,
                    'status' => 'paid',
                    'notes' => "Monthly fiber broadband service for {$cycleName}",
                    'created_at' => $periodStart . ' 09:00:00',
                    'updated_at' => $paymentDate,
                ]
            );

            // Invoice Item
            InvoiceItem::firstOrCreate(
                ['invoice_id' => $invoice->id, 'description' => "Optical Fiber Broadband Subscription ({$cycleName})"],
                [
                    'item_type' => 'package',
                    'quantity' => 1,
                    'unit_price' => $monthlyBill,
                    'total' => $monthlyBill,
                ]
            );

            // Generate Payment Receipt
            $payNumber = 'PAY-' . $cycleDate->format('Ym') . '-' . str_pad((string)$customer->id, 5, '0', STR_PAD_LEFT);

            $payment = Payment::firstOrCreate(
                ['payment_number' => $payNumber],
                [
                    'customer_id' => $customer->id,
                    'account_id' => $account?->id ?? 1,
                    'collected_by' => $admin->id,
                    'amount' => $monthlyBill,
                    'payment_method' => ($m['offset'] % 2 === 0 ? 'bkash' : 'cash'),
                    'reference' => 'TXN' . strtoupper(substr(md5($payNumber), 0, 10)),
                    'status' => 'completed',
                    'paid_at' => $paymentDate,
                    'notes' => "Full bill payment for {$cycleName}",
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]
            );

            // Payment Allocation
            PaymentAllocation::firstOrCreate(
                ['payment_id' => $payment->id, 'invoice_id' => $invoice->id],
                [
                    'allocated_amount' => $monthlyBill,
                ]
            );
        }

        echo "SUCCESS: Seeded 6+ months history for {$customer->name} (Username: {$username}, Password: {$password})" . PHP_EOL;
    }
}
