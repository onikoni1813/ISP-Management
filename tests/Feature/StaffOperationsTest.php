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
            'complaints.create',
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

    public function test_customer_moves_from_due_to_paid_filter_when_admin_approves_payment(): void
    {
        // Set customer connection to expired
        $this->connection->update(['expiry_date' => now()->subDay()->toDateString()]);

        // Verify customer initially appears in due filter
        $dueResBefore = $this->actingAs($this->staff)->getJson('/staff/api/filtered-customers?filter=due');
        $dueResBefore->assertStatus(200);
        $dueResBefore->assertJsonPath('counts.due', 1);
        $dueResBefore->assertJsonPath('counts.paid', 0);
        $this->assertCount(1, $dueResBefore->json('customers'));

        // Staff collects bill (status is pending)
        $this->actingAs($this->staff)->post("/customers/{$this->customer->id}/pay", [
            'amount' => 500.00,
            'payment_method' => 'cash',
        ]);

        $payment = Payment::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertEquals('pending', $payment->status);

        // Admin approves payment
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);
        $approvePerm = Permission::firstOrCreate(['slug' => 'billing.collect'], ['name' => 'billing.collect']);
        $adminRole->permissions()->syncWithoutDetaching([$approvePerm->id]);

        $this->actingAs($admin)->post("/admin/billing/payments/{$payment->id}/approve");

        $payment->refresh();
        $this->assertEquals('completed', $payment->status);

        // Verify connection expiry was extended
        $this->connection->refresh();
        $this->assertTrue(\Carbon\Carbon::parse($this->connection->expiry_date)->isFuture());

        // Now staff checks filtered customers: customer MUST leave due and be in paid
        $dueResAfter = $this->actingAs($this->staff)->getJson('/staff/api/filtered-customers?filter=due');
        $dueResAfter->assertStatus(200);
        $dueResAfter->assertJsonPath('counts.due', 0);
        $dueResAfter->assertJsonPath('counts.paid', 1);
        $this->assertCount(0, $dueResAfter->json('customers'));

        $paidRes = $this->actingAs($this->staff)->getJson('/staff/api/filtered-customers?filter=paid');
        $paidRes->assertStatus(200);
        $this->assertCount(1, $paidRes->json('customers'));
        $this->assertEquals($this->customer->id, $paidRes->json('customers.0.id'));
    }

    public function test_staff_and_admin_can_create_complaints(): void
    {
        // 1. Staff creates complaint with generic customer_id payload
        $response = $this->actingAs($this->staff)->post('/complaints', [
            'customer_id' => $this->customer->id,
            'subject' => 'লাল বাতি জ্বলছে (LOS Red Light)',
            'description' => 'কাস্টমারের অনুতে লাল বাতি জ্বলছে, ফাইবার চেক করতে হবে।',
            'priority' => 'high',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('complaints', [
            'customer_id' => $this->customer->id,
            'subject' => 'লাল বাতি জ্বলছে (LOS Red Light)',
            'priority' => 'high',
            'status' => 'open',
        ]);

        // 2. Admin creates complaint and assigns technician
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);
        $complaintPerm = Permission::firstOrCreate(['slug' => 'complaints.create'], ['name' => 'complaints.create']);
        $adminRole->permissions()->syncWithoutDetaching([$complaintPerm->id]);

        $adminResponse = $this->actingAs($admin)->post('/complaints', [
            'customer_id' => $this->customer->id,
            'subject' => 'ইন্টারনেট খুব ধীরগতি (Slow Internet)',
            'description' => 'ইউটিউব বাফারিং হচ্ছে, স্পিড চেক করুন।',
            'priority' => 'normal',
            'assigned_to' => $this->staff->id,
        ]);

        $adminResponse->assertStatus(302);
        $this->assertDatabaseHas('complaints', [
            'customer_id' => $this->customer->id,
            'subject' => 'ইন্টারনেট খুব ধীরগতি (Slow Internet)',
            'assigned_to' => $this->staff->id,
            'status' => 'assigned',
        ]);
    }
}
