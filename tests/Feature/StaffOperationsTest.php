<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected Customer $customer;
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $perms = [
            'customers.view',
            'billing.collect',
            'renewals.create',
        ];

        foreach ($perms as $slug) {
            $p = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug]);
            $staffRole->permissions()->attach($p);
        }

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-5001',
            'name' => 'Ziaul Hoque',
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
        ]);

        CustomerContact::create([
            'customer_id' => $this->customer->id,
            'contact_type' => 'primary',
            'phone' => '01811223344',
        ]);

        $pkg = Package::create(['name' => 'Standard', 'code' => 'PKG-STD', 'speed_mbps' => 10]);
        PackagePrice::create(['package_id' => $pkg->id, 'price' => 500.00, 'effective_from' => now(), 'status' => 'active']);

        $this->connection = Connection::create([
            'connection_code' => 'CON-5001',
            'customer_id' => $this->customer->id,
            'current_package_id' => $pkg->id,
            'status' => 'active',
            'expiry_date' => now()->addDays(5)->toDateString(),
        ]);
    }

    public function test_staff_dashboard_loads_metrics_correctly(): void
    {
        // Add a completed payment collected by staff today
        Payment::create([
            'payment_number' => 'PAY-2026-90999',
            'customer_id' => $this->customer->id,
            'amount' => 500.00,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'collected_by' => $this->staff->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->staff)->get('/staff/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => 
            $page->component('Staff/Dashboard')
                ->where('metrics.today_collection', 500)
                ->where('metrics.today_collection_count', 1)
        );
    }

    public function test_staff_live_search_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->staff)->getJson('/staff/api/search?q=0181122');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Ziaul Hoque']);
    }

    public function test_staff_can_view_customer_details_and_collect_payment(): void
    {
        $response = $this->actingAs($this->staff)->get("/staff/customers/{$this->customer->id}");
        $response->assertStatus(200);

        // Collect payment from field
        $payResponse = $this->actingAs($this->staff)->post("/customers/{$this->customer->id}/pay", [
            'amount' => 500.00,
            'payment_method' => 'cash',
        ]);

        $payResponse->assertStatus(302);
        $this->assertDatabaseHas('payments', [
            'customer_id' => $this->customer->id,
            'amount' => 500.00,
            'collected_by' => $this->staff->id,
        ]);
    }
}
