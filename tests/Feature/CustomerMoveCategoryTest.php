<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\BillingService;
use App\Services\CustomerService;
use App\Services\RenewalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerMoveCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected CustomerService $customerService;
    protected BillingService $billingService;
    protected RenewalService $renewalService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->customerService = app(CustomerService::class);
        $this->billingService = app(BillingService::class);
        $this->renewalService = app(RenewalService::class);
    }

    private function createCustomerWithConnection(string $name, float $price = 500.00): Customer
    {
        static $seq = 100;
        $customer = Customer::create([
            'customer_code' => 'CUST-MV' . ($seq++),
            'name' => $name,
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
            'billing_day' => 1,
        ]);

        $pkg = Package::firstOrCreate(
            ['code' => 'PKG-MV-' . (int)$price],
            ['name' => "Package {$price}", 'speed_mbps' => 10]
        );

        PackagePrice::firstOrCreate(
            ['package_id' => $pkg->id],
            ['price' => $price, 'validity_days' => 30, 'effective_from' => now()->subMonth(), 'status' => 'active']
        );

        Connection::create([
            'connection_code' => 'CONN-MV-' . $customer->id,
            'customer_id' => $customer->id,
            'current_package_id' => $pkg->id,
            'expiry_date' => now()->addDays(30)->toDateString(),
            'status' => 'active',
        ]);

        return $customer;
    }

    public function test_move_accidental_paid_renewal_to_zero_charge_grace(): void
    {
        $customer = $this->createCustomerWithConnection('Rahim Ahmed', 600.00);
        $connection = $customer->connections->first();

        // 1. Simulate accidental paid renewal (like user did by mistake)
        $this->renewalService->processRenewal($customer, $connection, [
            'package_id' => $connection->current_package_id,
            'validity_days' => 30,
            'is_zero_charge' => false,
            'amount' => 600.00,
            'collect_payment' => true,
            'payment_method' => 'cash',
        ], $this->admin->id);

        // Verify customer currently appears in paid_this_month
        $countsInitial = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $countsInitial['paid_this_month']);
        $this->assertEquals(0, $countsInitial['zero_charge_renewed']);

        // 2. Perform Move to zero_charge_renewed (3 days grace)
        $response = $this->actingAs($this->admin)->post(route('admin.customers.move-category', $customer->id), [
            'target_category' => 'zero_charge_renewed',
            'validity_days' => 3,
            'reverse_accidental_payment' => true,
            'void_renewal_invoice' => true,
            'notes' => 'Mistakenly renewed for 1 month, moved to 3 days grace',
        ]);

        $response->assertSessionHas('success');

        // Verify counts after move
        $countsAfter = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(0, $countsAfter['paid_this_month'], 'Customer must leave paid_this_month');
        $this->assertEquals(1, $countsAfter['zero_charge_renewed'], 'Customer must enter zero_charge_renewed');

        // Check connection expiry is now today + 3 days
        $connection->refresh();
        $this->assertEquals(now()->addDays(3)->toDateString(), $connection->expiry_date?->toDateString());

        // Check payment is reversed
        $payment = Payment::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals('reversed', $payment->status);
    }

    public function test_move_from_zero_charge_grace_to_paid_this_month(): void
    {
        $customer = $this->createCustomerWithConnection('Karim Mia', 500.00);

        // Initially move to zero charge
        $this->customerService->moveCustomerCategory($customer, 'zero_charge_renewed', ['validity_days' => 3], $this->admin->id);

        $countsGrace = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $countsGrace['zero_charge_renewed']);
        $this->assertEquals(0, $countsGrace['paid_this_month']);

        // Now move to paid_this_month
        $response = $this->actingAs($this->admin)->post(route('admin.customers.move-category', $customer->id), [
            'target_category' => 'paid_this_month',
            'payment_method' => 'bkash',
            'amount' => 500.00,
        ]);

        $response->assertSessionHas('success');

        $countsPaid = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $countsPaid['paid_this_month'], 'Customer must be in paid_this_month');
        $this->assertEquals(0, $countsPaid['zero_charge_renewed'], 'Customer must leave zero_charge_renewed');
    }

    public function test_bulk_move_customers(): void
    {
        $c1 = $this->createCustomerWithConnection('Bulk 1', 500.00);
        $c2 = $this->createCustomerWithConnection('Bulk 2', 500.00);

        $response = $this->actingAs($this->admin)->post(route('admin.customers.bulk-move-category'), [
            'target_category' => 'expiring_3d',
            'customer_ids' => [$c1->id, $c2->id],
            'validity_days' => 2,
        ]);

        $response->assertSessionHas('success');

        $counts = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(2, $counts['expiring_3d']);
    }

    public function test_move_customer_to_due(): void
    {
        $customer = $this->createCustomerWithConnection('Due Target', 500.00);

        // Move to due
        $response = $this->actingAs($this->admin)->post(route('admin.customers.move-category', $customer->id), [
            'target_category' => 'due',
            'amount' => 500.00,
        ]);

        $response->assertSessionHas('success');

        $counts = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $counts['due'], 'Customer must appear in due filter');
    }

    public function test_move_customer_to_expired(): void
    {
        $customer = $this->createCustomerWithConnection('Expired Target', 500.00);

        // Move to expired
        $response = $this->actingAs($this->admin)->post(route('admin.customers.move-category', $customer->id), [
            'target_category' => 'expired',
        ]);

        $response->assertSessionHas('success');

        $counts = $this->customerService->getCustomerFilterCounts();
        $this->assertEquals(1, $counts['expired'], 'Customer must appear in expired filter');
        
        $customer->refresh();
        $this->assertEquals('expired', $customer->status);
        $this->assertEquals('expired', $customer->connections->first()->status);
    }
}
