<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Complaint;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\PppoeCredential;
use App\Models\Role;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected User $customerUser1;
    protected User $customerUser2;
    protected Customer $customer1;
    protected Customer $customer2;
    protected Connection $connection1;
    protected Connection $connection2;
    protected Payment $payment1;
    protected Payment $payment2;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $customerRole = Role::create(['name' => 'Customer', 'slug' => 'customer']);

        $viewPasswordPerm = Permission::create([
            'name' => 'View PPPoE Password',
            'slug' => 'pppoe.view_password',
            'group' => 'security',
        ]);
        $customerViewPerm = Permission::create([
            'name' => 'View Customer',
            'slug' => 'customers.view',
            'group' => 'customer',
        ]);
        $packageUpdatePerm = Permission::create([
            'name' => 'Update Package',
            'slug' => 'packages.update',
            'group' => 'package',
        ]);
        $renewPerm = Permission::create([
            'name' => 'Create Renewal',
            'slug' => 'renewals.create',
            'group' => 'renewal',
        ]);

        $adminRole->permissions()->attach([$viewPasswordPerm->id, $customerViewPerm->id, $packageUpdatePerm->id, $renewPerm->id]);
        $staffRole->permissions()->attach([$customerViewPerm->id, $packageUpdatePerm->id, $renewPerm->id]); // Note: Staff lacks pppoe.view_password

        $this->admin = User::factory()->create(['email' => 'admin@sec.com']);
        $this->admin->roles()->attach($adminRole);

        $this->staff = User::factory()->create(['email' => 'staff@sec.com']);
        $this->staff->roles()->attach($staffRole);

        $this->customerUser1 = User::factory()->create(['email' => 'cust1@sec.com']);
        $this->customerUser1->roles()->attach($customerRole);

        $this->customerUser2 = User::factory()->create(['email' => 'cust2@sec.com']);
        $this->customerUser2->roles()->attach($customerRole);

        $area = Area::create(['name' => 'Sec Zone', 'code' => 'SZ-01', 'status' => 'active']);

        $this->package = Package::create(['name' => 'Sec 10M', 'code' => 'S10M', 'speed_mbps' => 10, 'status' => 'active']);
        PackagePrice::create([
            'package_id' => $this->package->id,
            'price' => 500,
            'validity_days' => 30,
            'effective_from' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ]);

        $this->customer1 = Customer::create([
            'user_id' => $this->customerUser1->id,
            'customer_code' => 'SEC-001',
            'name' => 'Alice Customer',
            'area_id' => $area->id,
            'status' => 'active',
            'balance' => 0,
            'billing_day' => 1,
            'join_date' => now()->toDateString(),
        ]);

        $this->customer2 = Customer::create([
            'user_id' => $this->customerUser2->id,
            'customer_code' => 'SEC-002',
            'name' => 'Bob Customer',
            'area_id' => $area->id,
            'status' => 'active',
            'balance' => 0,
            'billing_day' => 1,
            'join_date' => now()->toDateString(),
        ]);

        $this->connection1 = Connection::create([
            'customer_id' => $this->customer1->id,
            'connection_code' => 'CON-SEC-001',
            'current_package_id' => $this->package->id,
            'status' => 'active',
            'expiry_date' => now()->addDays(20)->toDateString(),
        ]);

        $this->connection2 = Connection::create([
            'customer_id' => $this->customer2->id,
            'connection_code' => 'CON-SEC-002',
            'current_package_id' => $this->package->id,
            'status' => 'active',
            'expiry_date' => now()->addDays(20)->toDateString(),
        ]);

        $billingService = app(BillingService::class);
        $this->payment1 = $billingService->collectPayment($this->customer1, [
            'amount' => 500,
            'payment_method' => 'bkash',
            'reference' => 'TXN-A1',
        ], $this->admin->id);

        $this->payment2 = $billingService->collectPayment($this->customer2, [
            'amount' => 500,
            'payment_method' => 'nagad',
            'reference' => 'TXN-B2',
        ], $this->admin->id);
    }

    public function test_security_headers_are_present_on_http_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_authenticated_responses_prevent_sensitive_caching(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_unauthorized_staff_cannot_reveal_pppoe_password(): void
    {
        $credential = PppoeCredential::create([
            'connection_id' => $this->connection1->id,
            'username' => 'alice_pppoe',
            'password' => 'supersecretpass', // auto-encrypted by model
        ]);

        // 1. Staff without pppoe.view_password cannot access
        $this->actingAs($this->staff)
            ->post(route('admin.pppoe.reveal-password', $credential->id))
            ->assertForbidden();

        // 2. Admin with permission can access and logs audit trail
        $res = $this->actingAs($this->admin)
            ->post(route('admin.pppoe.reveal-password', $credential->id));

        $res->assertOk()
            ->assertJson([
                'username' => 'alice_pppoe',
                'password' => 'supersecretpass',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pppoe_password_viewed',
            'module' => 'security',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_idor_protection_customer_cannot_view_another_customer_receipt(): void
    {
        // Customer 1 viewing their own receipt -> OK
        $this->actingAs($this->customerUser1)
            ->get(route('account.receipt', $this->payment1->id))
            ->assertOk();

        // Customer 1 attempting to view Customer 2's receipt -> 403 Forbidden (IDOR prevented)
        $this->actingAs($this->customerUser1)
            ->get(route('account.receipt', $this->payment2->id))
            ->assertForbidden();
    }

    public function test_idor_protection_customer_cannot_reply_to_another_customer_complaint(): void
    {
        $complaint2 = Complaint::create([
            'customer_id' => $this->customer2->id,
            'complaint_number' => 'CMP-SEC-999',
            'subject' => 'Bob Issue',
            'description' => 'Bob internet fault',
            'priority' => 'normal',
            'status' => 'open',
            'created_by' => $this->customerUser2->id,
        ]);

        // Customer 1 attempting to reply to Bob's ticket -> 403 Forbidden
        $this->actingAs($this->customerUser1)
            ->post(route('account.complaints.comment', $complaint2->id), [
                'comment' => 'Malicious unauthorized comment attempt',
            ])
            ->assertForbidden();
    }

    public function test_cross_entity_idor_validation_on_connection_actions(): void
    {
        // Connection 2 belongs to Customer 2. If an admin/staff tries to pass Customer 1 with Connection 2:
        $this->actingAs($this->admin)
            ->post(route('admin.customers.change-package', [
                'customer' => $this->customer1->id,
                'connection' => $this->connection2->id,
            ]), [
                'package_id' => $this->package->id,
            ])
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->post(route('customers.renew', [
                'customer' => $this->customer1->id,
                'connection' => $this->connection2->id,
            ]), [
                'validity_days' => 30,
            ])
            ->assertNotFound();
    }

    public function test_public_apply_endpoint_rate_limiting(): void
    {
        // Attempt 5 requests (within rate limit)
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('apply'), [
                'name' => "Tester {$i}",
                'phone' => "0170000000{$i}",
                'area_id' => $this->customer1->area_id,
                'package_id' => $this->package->id,
                'address' => 'Test Location',
            ]);
        }

        // 6th request must trigger HTTP 429 Too Many Requests
        $excessResponse = $this->post(route('apply'), [
            'name' => 'Spam Bot',
            'phone' => '01799999999',
            'area_id' => $this->customer1->area_id,
            'package_id' => $this->package->id,
            'address' => 'Spam Location',
        ]);

        $excessResponse->assertStatus(429);
    }
}
