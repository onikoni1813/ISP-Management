<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
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

        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);
        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);
    }

    public function test_admin_can_access_audit_logs_index(): void
    {
        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'customer_created',
            'module' => 'customer',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/audit/logs');
        $response->assertStatus(200);
        $response->assertInertia(fn($page) => 
            $page->component('Admin/Audit/Index')
                ->has('logs.data', 1)
        );
    }

    public function test_audit_logs_can_be_filtered_by_user_and_module(): void
    {
        AuditLog::create(['user_id' => $this->staff->id, 'action' => 'payment_collected', 'module' => 'billing']);
        AuditLog::create(['user_id' => $this->admin->id, 'action' => 'package_created', 'module' => 'package']);

        $response = $this->actingAs($this->admin)->get("/admin/audit/logs?user_id={$this->staff->id}&module=billing");
        $response->assertStatus(200);
        $response->assertInertia(fn($page) => 
            $page->component('Admin/Audit/Index')
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'payment_collected')
        );
    }

    public function test_staff_accountability_report_aggregates_totals_and_transactions(): void
    {
        $cust = Customer::create([
            'customer_code' => 'CUST-881',
            'name' => 'Kamal Hossain',
            'status' => 'active',
            'join_date' => now()->toDateString(),
        ]);

        Payment::create([
            'payment_number' => 'PAY-REC-01',
            'customer_id' => $cust->id,
            'amount' => 750.00,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'collected_by' => $this->staff->id,
            'status' => 'completed',
        ]);

        Payment::create([
            'payment_number' => 'PAY-REC-02',
            'customer_id' => $cust->id,
            'amount' => 250.00,
            'payment_method' => 'cash',
            'paid_at' => now(),
            'collected_by' => $this->staff->id,
            'status' => 'completed',
        ]);

        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($this->admin)->get("/admin/audit/staff-report?staff_id={$this->staff->id}&start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => 
            $page->component('Admin/Audit/StaffReport')
                ->where('stats.total_collections_amount', 1000)
                ->where('stats.total_collections_count', 2)
                ->has('collections', 2)
        );
    }
}
