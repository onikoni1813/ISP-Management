<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Complaint;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Role;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected Customer $customer;
    protected Connection $connection;
    protected Package $package;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $customerRole = Role::create(['name' => 'Customer', 'slug' => 'customer']);

        $this->customerUser = User::factory()->create([
            'name' => 'Monirul Islam',
            'email' => 'monir@pirgachainternet.com',
            'phone' => '01755443322',
        ]);
        $this->customerUser->roles()->attach($customerRole);

        $this->area = Area::create([
            'name' => 'College Road Zone',
            'code' => 'CRZ-01',
            'status' => 'active',
        ]);

        $this->package = Package::create([
            'name' => '15 Mbps Blast',
            'code' => 'PKG-15M',
            'speed_mbps' => 15,
            'is_active' => true,
        ]);

        PackagePrice::create([
            'package_id' => $this->package->id,
            'price' => 750.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_code' => 'CUST-000888',
            'name' => 'Monirul Islam',
            'area_id' => $this->area->id,
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
        ]);

        CustomerContact::create([
            'customer_id' => $this->customer->id,
            'contact_type' => 'primary',
            'phone' => '01755443322',
            'email' => 'monir@pirgachainternet.com',
        ]);

        CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'address_type' => 'installation',
            'full_address' => 'College Road, Pirgacha, Rangpur',
        ]);

        $this->connection = Connection::create([
            'customer_id' => $this->customer->id,
            'connection_code' => 'CON-000888',
            'current_package_id' => $this->package->id,
            'expiry_date' => now()->addDays(12)->toDateString(),
            'status' => 'active',
        ]);

        // Seed default SMS templates for notifications
        $this->seed(\Database\Seeders\SmsDefaultsSeeder::class);
    }

    public function test_customer_redirected_to_account_dashboard_from_root_dashboard(): void
    {
        $response = $this->actingAs($this->customerUser)->get(route('dashboard'));

        $response->assertRedirect(route('account.dashboard'));
    }

    public function test_customer_can_view_account_dashboard(): void
    {
        $response = $this->actingAs($this->customerUser)->get(route('account.dashboard'));

        $response->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Account/Dashboard')
                ->has('customer')
                ->has('primaryConnection')
                ->where('customer.customer_code', 'CUST-000888')
            );
    }

    public function test_customer_can_view_invoices_and_payments(): void
    {
        $billingService = app(BillingService::class);

        // Generate test invoice & payment
        $invoice = $billingService->createInvoice($this->customer, [
            'period_start' => now()->toDateString(),
            'period_end' => now()->addDays(30)->toDateString(),
            'items' => [
                ['description' => '15 Mbps Blast Internet', 'unit_price' => 750.00, 'quantity' => 1],
            ],
        ], $this->customerUser->id);

        $payment = $billingService->collectPayment($this->customer, [
            'amount' => 750.00,
            'payment_method' => 'bkash',
        ], $this->customerUser->id);

        // Customer can view dedicated Invoices page
        $this->actingAs($this->customerUser)
            ->get('/account/invoices')
            ->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Account/Invoices')
            );

        // Check Payments page
        $this->actingAs($this->customerUser)
            ->get(route('account.payments'))
            ->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Account/Payments')
                ->has('payments.data', 1)
            );
    }

    public function test_customer_can_submit_complaint_and_comment(): void
    {
        // 1. Submit ticket
        $ticketRes = $this->actingAs($this->customerUser)->post(route('account.complaints.store'), [
            'subject' => 'Internet dropping every 10 minutes',
            'description' => 'Frequent disconnections since evening',
            'priority' => 'high',
        ]);

        $ticketRes->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'customer_id' => $this->customer->id,
            'subject' => 'Internet dropping every 10 minutes',
            'status' => 'open',
        ]);

        $complaint = Complaint::where('customer_id', $this->customer->id)->first();

        // 2. Add reply comment
        $commentRes = $this->actingAs($this->customerUser)->post(route('account.complaints.comment', $complaint->id), [
            'comment' => 'Router light is blinking red right now.',
        ]);

        $commentRes->assertRedirect();

        $this->assertDatabaseHas('complaint_comments', [
            'complaint_id' => $complaint->id,
            'comment' => 'Router light is blinking red right now.',
        ]);

        // 3. View complaints page receives dynamic nocHotline
        $this->actingAs($this->customerUser)->get(route('account.complaints'))
            ->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Account/Complaints')
                ->has('nocHotline')
                ->has('complaints')
            );
    }

    public function test_customer_can_self_renew_connection(): void
    {
        $initialExpiry = $this->connection->expiry_date;

        $response = $this->actingAs($this->customerUser)->post(route('account.renewal.store'), [
            'validity_days' => 30,
            'payment_method' => 'bkash',
            'reference' => 'BKASH-TXN-12345',
        ]);

        $response->assertRedirect(route('account.dashboard'));

        $this->connection->refresh();

        // Must extend from current expiry date
        $this->assertNotEquals($initialExpiry, $this->connection->expiry_date);

        $this->assertDatabaseHas('renewals', [
            'customer_id' => $this->customer->id,
            'connection_id' => $this->connection->id,
            'validity_days' => 30,
        ]);
    }

    public function test_customer_can_view_upgrade_page_and_upgrade_package(): void
    {
        // Create a new package to upgrade to
        $newPackage = Package::create([
            'name' => 'Ultra 20 Mbps',
            'code' => 'PKG-20M',
            'speed_mbps' => 20,
            'is_active' => true,
        ]);
        PackagePrice::create([
            'package_id' => $newPackage->id,
            'price' => 1000.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
        ]);

        // 1. View upgrade page
        $this->actingAs($this->customerUser)->get(route('account.upgrade'))
            ->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Account/Upgrade')
                ->has('packages')
            );

        // 2. Submit upgrade
        $response = $this->actingAs($this->customerUser)->post(route('account.upgrade.store'), [
            'package_id' => $newPackage->id,
        ]);

        $response->assertRedirect(route('account.dashboard'));

        $this->connection->refresh();
        $this->assertEquals($newPackage->id, $this->connection->current_package_id);

        $this->assertDatabaseHas('customer_packages', [
            'customer_id' => $this->customer->id,
            'connection_id' => $this->connection->id,
            'package_id' => $newPackage->id,
            'status' => 'active',
        ]);
    }
}
