<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Complaint;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Renewal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflinePwaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected Customer $customer;
    protected Connection $connection;
    protected Package $package;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin & Staff roles
        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);

        // Permissions
        $permissions = [
            'customers.view',
            'billing.collect',
            'renewals.create',
            'complaints.create',
            'complaints.update',
        ];

        foreach ($permissions as $permName) {
            $p = Permission::firstOrCreate(['slug' => $permName], ['name' => $permName]);
            $staffRole->permissions()->attach($p);
        }

        // Test Area
        $this->area = Area::create([
            'name' => 'Pirgacha Bazar Zone',
            'code' => 'PBZ-01',
            'status' => 'active',
        ]);

        // Test Package
        $this->package = Package::create([
            'name' => '10 Mbps Standard',
            'code' => 'PKG-10M',
            'speed_mbps' => 10,
            'is_active' => true,
        ]);

        PackagePrice::create([
            'package_id' => $this->package->id,
            'price' => 600.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
        ]);

        // Test Customer & Connection
        $this->customer = Customer::create([
            'customer_code' => 'CUST-000777',
            'name' => 'Tariqul Islam',
            'area_id' => $this->area->id,
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
        ]);

        CustomerContact::create([
            'customer_id' => $this->customer->id,
            'contact_type' => 'primary',
            'phone' => '01799887766',
        ]);

        $this->connection = Connection::create([
            'customer_id' => $this->customer->id,
            'connection_code' => 'CON-000777',
            'current_package_id' => $this->package->id,
            'expiry_date' => now()->addDays(5)->toDateString(),
            'status' => 'active',
        ]);
    }

    public function test_staff_can_download_bootstrap_offline_cache(): void
    {
        $response = $this->actingAs($this->staff)->getJson(route('staff.sync.bootstrap'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'timestamp',
                'server_version',
                'data' => [
                    'customers',
                    'packages',
                    'areas',
                    'complaints',
                ],
            ]);

        $this->assertCount(1, $response->json('data.customers'));
        $this->assertEquals('CUST-000777', $response->json('data.customers.0.customer_code'));
        $this->assertEquals('Pirgacha Bazar Zone', $response->json('data.areas.0.name'));
    }

    public function test_offline_payment_mutation_processing_with_idempotency(): void
    {
        $mutationUuid = 'mut_offline_' . (string) Str::uuid();

        $payload = [
            'mutations' => [
                [
                    'uuid' => $mutationUuid,
                    'action' => 'collect_payment',
                    'payload' => [
                        'customer_id' => $this->customer->id,
                        'amount' => 600.00,
                        'payment_method' => 'cash',
                        'notes' => 'Collected in field during network outage',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        // 1. Process batch mutations
        $response = $this->actingAs($this->staff)->postJson(route('staff.sync.mutations'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'processed_count' => 1,
            ]);

        $this->assertEquals('success', $response->json('results.0.status'));

        $this->assertDatabaseHas('payments', [
            'customer_id' => $this->customer->id,
            'amount' => 600.00,
            'idempotency_key' => $mutationUuid,
            'collected_by' => $this->staff->id,
        ]);

        // 2. Retry with same UUID (Rule 6 Idempotency)
        $retryResponse = $this->actingAs($this->staff)->postJson(route('staff.sync.mutations'), $payload);

        $retryResponse->assertOk()
            ->assertJson([
                'success' => true,
                'processed_count' => 1,
            ]);

        // Must still only have 1 payment recorded
        $this->assertEquals(1, Payment::where('customer_id', $this->customer->id)->count());
    }

    public function test_offline_renewal_mutation_processing(): void
    {
        $mutationUuid = 'mut_renew_' . (string) Str::uuid();

        $payload = [
            'mutations' => [
                [
                    'uuid' => $mutationUuid,
                    'action' => 'renew_connection',
                    'payload' => [
                        'customer_id' => $this->customer->id,
                        'connection_id' => $this->connection->id,
                        'validity_days' => 30,
                        'is_zero_charge' => false,
                        'mode' => 'standard',
                        'collect_payment' => true,
                        'payment_method' => 'cash',
                        'notes' => 'Field offline renewal',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)->postJson(route('staff.sync.mutations'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'processed_count' => 1,
            ]);

        $this->assertEquals('success', $response->json('results.0.status'));

        $this->assertDatabaseHas('renewals', [
            'customer_id' => $this->customer->id,
            'connection_id' => $this->connection->id,
            'idempotency_key' => $mutationUuid,
        ]);
    }

    public function test_offline_complaint_mutation_and_conflict_detection(): void
    {
        // 1. Create Complaint offline
        $createUuid = 'mut_tkt_create_' . (string) Str::uuid();

        $createPayload = [
            'mutations' => [
                [
                    'uuid' => $createUuid,
                    'action' => 'create_complaint',
                    'payload' => [
                        'customer_id' => $this->customer->id,
                        'subject' => 'Router red light blinking in field',
                        'description' => 'Customer reported LOS fault',
                        'priority' => 'high',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        $res = $this->actingAs($this->staff)->postJson(route('staff.sync.mutations'), $createPayload);
        $res->assertOk();
        $ticketId = $res->json('results.0.entity_id');

        $this->assertDatabaseHas('complaints', [
            'id' => $ticketId,
            'subject' => 'Router red light blinking in field',
        ]);

        // 2. Simulate server-side conflict: Ticket is resolved on server by admin
        $complaint = Complaint::find($ticketId);
        $complaint->update([
            'status' => 'resolved',
            'resolved_by' => $this->admin->id,
            'resolved_at' => now(),
            'resolution_note' => 'Fixed by main office NOC',
        ]);

        // 3. Staff offline device attempts to update status to in_progress (Section 39 Conflict Handling)
        $updateUuid = 'mut_tkt_update_' . (string) Str::uuid();
        $updatePayload = [
            'mutations' => [
                [
                    'uuid' => $updateUuid,
                    'action' => 'update_complaint_status',
                    'payload' => [
                        'complaint_id' => $complaint->id,
                        'status' => 'in_progress',
                        'note' => 'Technician on the way',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        $conflictRes = $this->actingAs($this->staff)->postJson(route('staff.sync.mutations'), $updatePayload);

        $conflictRes->assertOk();
        $this->assertEquals('conflict', $conflictRes->json('results.0.status'));
        $this->assertStringContainsString('Conflict', $conflictRes->json('results.0.message'));
    }
}
