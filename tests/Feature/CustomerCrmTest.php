<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrmTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $viewPerm = Permission::create(['name' => 'View Customers', 'slug' => 'customers.view']);
        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $staffRole->permissions()->attach($viewPerm);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);
    }

    public function test_admin_can_view_customers_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/customers');
        $response->assertStatus(200);
    }

    public function test_customer_creation_creates_code_contact_address_and_audit_log(): void
    {
        $area = Area::create(['name' => 'Sadar', 'code' => 'AREA-01']);
        $package = Package::create(['name' => '10M', 'code' => 'P10', 'speed_mbps' => 10]);
        PackagePrice::create([
            'package_id' => $package->id,
            'price' => 500.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonth(),
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Delwar Hossain',
            'phone' => '01712345678',
            'email' => 'delwar@example.com',
            'area_id' => $area->id,
            'address' => 'Station Road, Pirgacha',
            'package_id' => $package->id,
            'pppoe_username' => 'delwar_net',
            'pppoe_password' => 'secret1234',
        ];

        $response = $this->actingAs($this->admin)->post('/admin/customers', $payload);

        $customer = Customer::where('name', 'Delwar Hossain')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('CUST-000001', $customer->customer_code);

        // Verify contact created
        $this->assertDatabaseHas('customer_contacts', [
            'customer_id' => $customer->id,
            'phone' => '01712345678',
        ]);

        // Verify audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer_created',
            'entity_id' => $customer->id,
        ]);
    }

    public function test_pppoe_password_reveal_requires_permission_and_creates_audit_log(): void
    {
        $area = Area::create(['name' => 'Sadar', 'code' => 'AREA-02']);
        $package = Package::create(['name' => '10M', 'code' => 'P11', 'speed_mbps' => 10]);
        PackagePrice::create([
            'package_id' => $package->id,
            'price' => 500.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonth(),
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post('/admin/customers', [
            'name' => 'Test Customer',
            'phone' => '01700000000',
            'area_id' => $area->id,
            'address' => 'Pirgacha',
            'package_id' => $package->id,
            'pppoe_username' => 'test_pppoe_user',
            'pppoe_password' => 'secure_password_99',
        ]);

        $customer = Customer::where('name', 'Test Customer')->first();
        $credential = $customer->connections->first()->pppoeCredential;

        // Staff without pppoe.view_password cannot reveal password
        $unauthorized = $this->actingAs($this->staff)->postJson("/admin/pppoe/{$credential->id}/reveal-password");
        $unauthorized->assertStatus(403);

        // Admin can reveal password and logs audit
        $authorized = $this->actingAs($this->admin)->postJson("/admin/pppoe/{$credential->id}/reveal-password");
        $authorized->assertStatus(200);
        $authorized->assertJson(['password' => 'secure_password_99']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pppoe_password_viewed',
            'entity_id' => $credential->id,
        ]);
    }
}
