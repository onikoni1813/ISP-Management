<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);

        $viewAuditPerm = Permission::create(['name' => 'View Audit', 'slug' => 'audit.view']);
        $viewCustPerm = Permission::create(['name' => 'View Customers', 'slug' => 'customers.view']);

        $adminRole->permissions()->attach([$viewAuditPerm->id, $viewCustPerm->id]);
        $staffRole->permissions()->attach([$viewCustPerm->id]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@pirgachha.net',
            'status' => 'active',
        ]);
        $this->adminUser->roles()->attach($adminRole->id);

        $this->staffUser = User::factory()->create([
            'name' => 'Staff Technician',
            'email' => 'tech@pirgachha.net',
            'status' => 'active',
        ]);
        $this->staffUser->roles()->attach($staffRole->id);
    }

    public function test_composite_indexes_exist_on_connections_and_audit_logs(): void
    {
        $connectionIndexes = collect(\Illuminate\Support\Facades\Schema::getIndexes('connections'))->pluck('name')->toArray();

        $this->assertContains('connections_customer_status_idx', $connectionIndexes);
        $this->assertContains('connections_expiry_status_idx', $connectionIndexes);

        $auditIndexes = collect(\Illuminate\Support\Facades\Schema::getIndexes('audit_logs'))->pluck('name')->toArray();

        $this->assertContains('audit_logs_user_created_idx', $auditIndexes);
        $this->assertContains('audit_logs_module_created_idx', $auditIndexes);
    }

    public function test_audit_log_distinct_modules_and_actions_are_cached(): void
    {
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'customer_created',
            'module' => 'customer',
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'payment_collected',
            'module' => 'billing',
            'ip_address' => '127.0.0.1',
        ]);

        Cache::flush();

        $this->assertFalse(Cache::has('audit_distinct_modules'));
        $this->assertFalse(Cache::has('audit_distinct_actions'));

        $response = $this->actingAs($this->adminUser)->get(route('admin.audit.index'));
        $response->assertOk();

        $this->assertTrue(Cache::has('audit_distinct_modules'));
        $this->assertTrue(Cache::has('audit_distinct_actions'));

        $cachedModules = Cache::get('audit_distinct_modules');
        $this->assertContains('customer', $cachedModules);
        $this->assertContains('billing', $cachedModules);
    }

    public function test_staff_dashboard_queries_open_complaints_with_index(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-000001',
            'name' => 'Rahim Uddin',
            'status' => 'active',
            'join_date' => '2026-09-01',
            'balance' => 0.00,
        ]);

        Complaint::create([
            'complaint_number' => 'TKT-2026-000001',
            'customer_id' => $customer->id,
            'assigned_to' => $this->staffUser->id,
            'subject' => 'Fiber wire cut',
            'description' => 'Main line broken near Pirgacha college',
            'priority' => 'high',
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($this->staffUser)->get(route('staff.dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Staff/Dashboard')
                ->where('metrics.open_complaints_count', 1)
        );
    }

    public function test_customer_search_eager_loading_avoids_n_plus_one(): void
    {
        $package = Package::create([
            'name' => 'Silver 10M',
            'code' => 'PKG-10M',
            'speed_limit' => '10 Mbps',
            'price' => 500.00,
            'billing_type' => 'prepaid',
            'status' => 'active',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $cust = Customer::create([
                'customer_code' => "CUST-00000{$i}",
                'name' => "Subscriber {$i}",
                'status' => 'active',
                'join_date' => '2026-09-01',
                'balance' => 0.00,
            ]);

            Connection::create([
                'connection_code' => "CON-00000{$i}",
                'customer_id' => $cust->id,
                'current_package_id' => $package->id,
                'status' => 'active',
            ]);
        }

        $service = app(CustomerService::class);

        DB::enableQueryLog();
        $results = $service->searchCustomers('Subscriber', 'active');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals(5, $results->total());
        // Should execute minimal queries: count + select customers + eager load relations
        $this->assertLessThanOrEqual(7, $queryCount, "Query count should remain bounded and not scale with customer count.");
    }
}
