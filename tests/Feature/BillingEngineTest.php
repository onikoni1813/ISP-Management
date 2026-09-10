<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-000100',
            'name' => 'Abdur Rahman',
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
        ]);

        $this->billingService = app(BillingService::class);
    }

    public function test_invoice_creation_with_line_items_and_customer_balance(): void
    {
        $invoice = $this->billingService->createInvoice($this->customer, [
            'items' => [
                ['description' => '10 Mbps Standard', 'unit_price' => 500.00, 'quantity' => 1],
                ['description' => 'Optical Fiber Patch Cord', 'unit_price' => 150.00, 'quantity' => 1],
            ],
            'discount' => 50.00,
            'due_date' => now()->addDays(7)->toDateString(),
        ], $this->admin->id);

        $this->assertEquals(650.00, (float) $invoice->subtotal);
        $this->assertEquals(600.00, (float) $invoice->total);
        $this->assertEquals(600.00, (float) $invoice->due_amount);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals(600.00, (float) $this->customer->fresh()->balance);
    }

    public function test_partial_payment_and_multi_invoice_allocation(): void
    {
        // Invoice 1: ৳500
        $inv1 = $this->billingService->createInvoice($this->customer, [
            'items' => [['description' => 'Monthly Fee', 'unit_price' => 500.00, 'quantity' => 1]],
            'due_date' => now()->addDays(2)->toDateString(),
        ], $this->admin->id);

        // Invoice 2: ৳200
        $inv2 = $this->billingService->createInvoice($this->customer, [
            'items' => [['description' => 'Equipment Fee', 'unit_price' => 200.00, 'quantity' => 1]],
            'due_date' => now()->addDays(5)->toDateString(),
        ], $this->admin->id);

        // Partial payment of ৳600 (should fully pay Inv 1 [৳500], and partially pay Inv 2 [৳100])
        $payment = $this->billingService->collectPayment($this->customer, [
            'amount' => 600.00,
            'payment_method' => 'cash',
        ], $this->admin->id);

        $inv1->refresh();
        $inv2->refresh();

        $this->assertEquals('paid', $inv1->status);
        $this->assertEquals(500.00, (float) $inv1->paid_amount);
        $this->assertEquals(0.00, (float) $inv1->due_amount);

        $this->assertEquals('partial', $inv2->status);
        $this->assertEquals(100.00, (float) $inv2->paid_amount);
        $this->assertEquals(100.00, (float) $inv2->due_amount);

        // Net balance should be remaining due: ৳700 - ৳600 = ৳100
        $this->assertEquals(100.00, (float) $this->customer->fresh()->balance);

        // Assert 2 allocations created
        $this->assertCount(2, $payment->allocations);
    }

    public function test_duplicate_payment_prevention_via_idempotency_key(): void
    {
        $inv = $this->billingService->createInvoice($this->customer, [
            'items' => [['description' => 'Fee', 'unit_price' => 500.00, 'quantity' => 1]],
        ], $this->admin->id);

        $payload = [
            'amount' => 500.00,
            'payment_method' => 'bkash',
            'idempotency_key' => 'uuid-client-test-unique-12345',
        ];

        // 1st request
        $pay1 = $this->billingService->collectPayment($this->customer, $payload, $this->admin->id);

        // 2nd request with same idempotency key (simulating offline network duplicate retry)
        $pay2 = $this->billingService->collectPayment($this->customer, $payload, $this->admin->id);

        // Must return identical payment record without creating two payments
        $this->assertEquals($pay1->id, $pay2->id);
        $this->assertEquals(1, Payment::count());
        $this->assertEquals(500.00, (float) $pay1->amount);
    }

    public function test_payment_reversal_restores_balances_and_marks_reversed(): void
    {
        $account = Account::create(['name' => 'Main Cash', 'balance' => 0.00]);

        $inv = $this->billingService->createInvoice($this->customer, [
            'items' => [['description' => 'Package', 'unit_price' => 500.00, 'quantity' => 1]],
        ], $this->admin->id);

        $payment = $this->billingService->collectPayment($this->customer, [
            'amount' => 500.00,
            'payment_method' => 'cash',
            'account_id' => $account->id,
        ], $this->admin->id);

        $this->assertEquals('paid', $inv->fresh()->status);
        $this->assertEquals(500.00, (float) $account->fresh()->balance);

        // Reverse payment
        $this->billingService->reversePayment($payment, 'Customer check bounced / entry mistake', $this->admin->id);

        $this->assertEquals('reversed', $payment->fresh()->status);
        $this->assertEquals('unpaid', $inv->fresh()->status);
        $this->assertEquals(0.00, (float) $account->fresh()->balance);
        $this->assertEquals(500.00, (float) $this->customer->fresh()->balance);
    }
}
