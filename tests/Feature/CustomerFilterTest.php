<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Role;
use App\Models\User;
use App\Services\BillingService;
use App\Services\CustomerService;
use App\Services\RenewalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected CustomerService $customerService;
    protected BillingService $billingService;
    protected RenewalService $renewalService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->customerService = app(CustomerService::class);
        $this->billingService = app(BillingService::class);
        $this->renewalService = app(RenewalService::class);
    }

    private function createCustomerWithPackage(string $name, float $price = 500.00, string $expiryDate = null): array
    {
        static $seq = 1;
        $customer = Customer::create([
            'customer_code' => 'CUST-F' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT),
            'name' => $name,
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
        ]);

        $pkg = Package::firstOrCreate(
            ['code' => 'PKG-TEST-' . (int)$price],
            ['name' => "Package {$price}", 'speed_mbps' => 10]
        );

        PackagePrice::firstOrCreate(
            ['package_id' => $pkg->id],
            ['price' => $price, 'validity_days' => 30, 'effective_from' => now()->subMonth(), 'status' => 'active']
        );

        $conn = Connection::create([
            'connection_code' => 'CON-F' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'current_package_id' => $pkg->id,
            'status' => 'active',
            'expiry_date' => $expiryDate ?: now()->addDays(15)->toDateString(),
        ]);

        $seq++;
        return [$customer, $conn, $pkg];
    }

    public function test_paid_this_month_includes_customer_who_paid_monthly_bill(): void
    {
        [$customer, $conn] = $this->createCustomerWithPackage('Paid Customer A');

        // Customer pays monthly bill via collectPayment
        $this->billingService->collectPayment($customer, [
            'amount' => 500.00,
            'payment_method' => 'cash',
        ], $this->admin->id);

        $filterCounts = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $filterCounts['paid_this_month']);
        $this->assertEquals(0, $filterCounts['due']);

        $searchResults = $this->customerService->searchCustomers(advancedFilter: 'paid_this_month');
        $this->assertEquals(1, $searchResults->total());
        $this->assertEquals($customer->id, $searchResults->items()[0]->id);
    }

    public function test_paid_this_month_captures_payments_on_last_day_of_month(): void
    {
        [$customer] = $this->createCustomerWithPackage('End Of Month Payer');

        // Pay at 22:30 on the last day of current month
        $this->billingService->collectPayment($customer, [
            'amount' => 500.00,
            'payment_method' => 'bkash',
            'paid_at' => now()->endOfMonth()->setTime(22, 30, 0),
        ], $this->admin->id);

        $filterCounts = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $filterCounts['paid_this_month']);

        $searchResults = $this->customerService->searchCustomers(advancedFilter: 'paid_this_month');
        $this->assertEquals(1, $searchResults->total());
    }

    public function test_paid_this_month_excludes_customer_with_unpaid_due_invoice(): void
    {
        [$customer] = $this->createCustomerWithPackage('Partial Payer');

        // Create invoice of 1000
        $inv = $this->billingService->createInvoice($customer, [
            'items' => [['description' => '2 Months Bill', 'unit_price' => 1000.00, 'quantity' => 1]],
            'due_date' => now()->toDateString(),
        ], $this->admin->id);

        // Pay only 400 (partial, leaving 600 due)
        $this->billingService->collectPayment($customer, [
            'amount' => 400.00,
            'payment_method' => 'cash',
            'invoice_ids' => [$inv->id],
        ], $this->admin->id);

        $filterCounts = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(0, $filterCounts['paid_this_month'], 'Customer with remaining due invoice must NOT show in paid_this_month');
        $this->assertEquals(1, $filterCounts['due'], 'Customer with partial unpaid invoice MUST show in due');
    }

    public function test_standard_paid_renewal_appears_in_paid_this_month(): void
    {
        [$customer, $conn] = $this->createCustomerWithPackage('Renewal Customer');

        $this->renewalService->processRenewal($customer, $conn, [
            'validity_days' => 30,
            'amount' => 500.00,
            'is_zero_charge' => false,
            'mode' => 'standard',
            'collect_payment' => true,
            'payment_method' => 'cash',
        ], $this->admin->id);

        $filterCounts = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $filterCounts['paid_this_month']);
        $this->assertEquals(0, $filterCounts['due']);
        $this->assertEquals(0, $filterCounts['zero_charge_renewed']);
    }

    public function test_zero_charge_renewal_lifecycle(): void
    {
        [$customer, $conn] = $this->createCustomerWithPackage('Grace Customer');

        // 1. Give zero-charge grace renewal
        $this->renewalService->processRenewal($customer, $conn, [
            'validity_days' => 3,
            'is_zero_charge' => true,
            'mode' => 'standard',
        ], $this->admin->id);

        $counts = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $counts['zero_charge_renewed']);
        $this->assertEquals(0, $counts['paid_this_month']);

        // 2. Customer pays bill later
        $this->billingService->collectPayment($customer, [
            'amount' => 500.00,
            'payment_method' => 'cash',
            'paid_at' => now()->addMinute(),
        ], $this->admin->id);

        $countsAfterPayment = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(0, $countsAfterPayment['zero_charge_renewed'], 'Should clear from zero_charge_renewed once paid');
        $this->assertEquals(1, $countsAfterPayment['paid_this_month'], 'Should now show in paid_this_month');
    }

    public function test_expiring_and_expired_filters(): void
    {
        // Expiring in 2 days
        $this->createCustomerWithPackage('Expiring Soon', 500.00, now()->addDays(2)->toDateString());

        // Already expired 2 days ago
        $this->createCustomerWithPackage('Already Expired', 500.00, now()->subDays(2)->toDateString());

        $counts = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $counts['expiring_3d']);
        $this->assertEquals(1, $counts['expired']);
    }
}
