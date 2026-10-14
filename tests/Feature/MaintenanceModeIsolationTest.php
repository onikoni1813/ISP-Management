<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PppoeCredential;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected User $customerUser;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Roles
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
        $customerRole = Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer']);

        $perm = \App\Models\Permission::firstOrCreate(['slug' => 'complaints.view'], ['name' => 'View complaints', 'module' => 'complaints']);
        $staffRole->permissions()->syncWithoutDetaching([$perm->id]);

        // 2. Setup Users
        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->roles()->sync([$adminRole->id]);

        $this->staff = User::factory()->create(['email' => 'staff@test.com']);
        $this->staff->roles()->sync([$staffRole->id]);

        $this->customerUser = User::factory()->create([
            'email' => 'customer@test.com',
            'username' => 'rashed_test',
            'password' => bcrypt('secret123'),
        ]);
        $this->customerUser->roles()->sync([$customerRole->id]);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-0099',
            'user_id' => $this->customerUser->id,
            'name' => 'Rashed Test Customer',
            'status' => 'active',
            'join_date' => now(),
            'billing_day' => 1,
            'balance' => 0,
        ]);

        // Enable Maintenance Mode
        Setting::set('maintenance_mode', '1');
        Setting::set('maintenance_title', 'আমরা রক্ষণাবেক্ষণ করছি');
    }

    public function test_only_homepage_returns_503_in_maintenance_mode(): void
    {
        // Public visitor on Homepage
        $resHome = $this->get('/');
        $resHome->assertStatus(503);
        $resHome->assertSee('আমরা রক্ষণাবেক্ষণ করছি');

        // Other public pages are completely accessible
        $this->get('/packages')->assertOk();
        $this->get('/coverage')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/faq')->assertOk();
        $this->get('/notices')->assertOk();
        $this->get('/contact')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/admin/login')->assertOk();
        $this->get('/staff/login')->assertOk();
    }

    public function test_pppoe_customer_can_login_and_access_portal_during_maintenance(): void
    {
        // Subscriber logs in
        $response = $this->post('/login', [
            'login' => 'customer@test.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('account.dashboard'));

        // Customer can access account dashboard and sub-modules
        $this->actingAs($this->customerUser)->get(route('account.dashboard'))->assertOk();
        $this->actingAs($this->customerUser)->get(route('account.invoices'))->assertOk();
        $this->actingAs($this->customerUser)->get(route('account.payments'))->assertOk();
        $this->actingAs($this->customerUser)->get(route('account.renewal'))->assertOk();
        $this->actingAs($this->customerUser)->get(route('account.complaints'))->assertOk();
    }

    public function test_staff_can_access_staff_portal_during_maintenance(): void
    {
        $this->actingAs($this->staff)->get(route('staff.dashboard'))->assertOk();
        $this->actingAs($this->staff)->get(route('staff.complaints.index'))->assertRedirect(route('staff.dashboard', ['tab' => 'complaints']));
    }

    public function test_admin_can_access_admin_portal_and_preview_homepage(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.customers.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.billing.invoices'))->assertOk();

        // Admin can preview live homepage using ?preview=1
        $this->actingAs($this->admin)->get('/?preview=1')->assertOk();
    }
}
