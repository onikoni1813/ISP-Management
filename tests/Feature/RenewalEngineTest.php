<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Renewal;
use App\Models\Role;
use App\Models\User;
use App\Services\RenewalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenewalEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected Package $package;
    protected Connection $connection;
    protected RenewalService $renewalService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-3001',
            'name' => 'Moniruzzaman',
            'status' => 'active',
            'join_date' => now()->subMonths(2)->toDateString(),
            'balance' => 0.00,
        ]);

        $this->package = Package::create([
            'name' => '10 Mbps Standard',
            'code' => 'PKG-10',
            'speed_mbps' => 10,
        ]);

        PackagePrice::create([
            'package_id' => $this->package->id,
            'price' => 500.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonth(),
            'status' => 'active',
        ]);

        $this->connection = Connection::create([
            'connection_code' => 'CON-3001',
            'customer_id' => $this->customer->id,
            'current_package_id' => $this->package->id,
            'status' => 'active',
            'installation_date' => now()->subMonths(2)->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->renewalService = app(RenewalService::class);
    }

    public function test_standard_renewal_extends_from_current_expiry_and_creates_invoice(): void
    {
        $currentExpiry = Carbon::parse($this->connection->expiry_date);

        $renewal = $this->renewalService->processRenewal($this->customer, $this->connection, [
            'validity_days' => 30,
            'amount' => 500.00,
            'is_zero_charge' => false,
        ], $this->admin->id);

        $expectedExpiry = $currentExpiry->copy()->addDays(30)->toDateString();

        $this->assertEquals($expectedExpiry, $renewal->new_expiry->toDateString());
        $this->assertEquals($expectedExpiry, $this->connection->fresh()->expiry_date->toDateString());
        $this->assertNotNull($renewal->invoice_id);
        $this->assertEquals('early', $renewal->renewal_type);
        $this->assertEquals(500.00, (float) $renewal->amount);
    }

    public function test_expired_customer_renewal_starts_from_today(): void
    {
        // Set connection to expired in the past
        $this->connection->update([
            'expiry_date' => now()->subDays(5)->toDateString(),
            'status' => 'expired',
        ]);

        $renewal = $this->renewalService->processRenewal($this->customer, $this->connection, [
            'validity_days' => 30,
            'amount' => 500.00,
            'is_zero_charge' => false,
        ], $this->admin->id);

        $expectedExpiry = Carbon::today()->addDays(30)->toDateString();

        $this->assertEquals($expectedExpiry, $renewal->new_expiry->toDateString());
        $this->assertEquals('expired', $renewal->renewal_type);
        $this->assertEquals('active', $this->connection->fresh()->status);
    }

    public function test_user_rule_zero_charge_validity_deduction_and_shift(): void
    {
        // The user's specific business rule:
        // "যে কয়দিন এর রিনিউ করবে ইউসার এর প্যাকেজ থেকে সে কয়দিন মেয়াদ কমে যাবে, রিনিউ এর জন্য কোনো চার্জ লাগবে না"
        $currentExpiry = Carbon::parse($this->connection->expiry_date); // 10 days in future

        $renewal = $this->renewalService->processRenewal($this->customer, $this->connection, [
            'validity_days' => 4, // 4 days reduced
            'is_zero_charge' => true,
            'mode' => 'deduct_shift',
        ], $this->admin->id);

        $expectedNewExpiry = $currentExpiry->copy()->subDays(4)->toDateString();

        $this->assertEquals($expectedNewExpiry, $renewal->new_expiry->toDateString());
        $this->assertEquals(0.00, (float) $renewal->amount);
        $this->assertTrue($renewal->is_zero_charge);
        $this->assertNull($renewal->invoice_id); // No invoice or extra payment charged
        $this->assertEquals(0.00, (float) $this->customer->fresh()->balance); // Customer balance unaffected
    }

    public function test_renewal_idempotency_prevents_duplicate_transactions(): void
    {
        $payload = [
            'validity_days' => 30,
            'amount' => 500.00,
            'idempotency_key' => 'unique-ren-key-999',
        ];

        $ren1 = $this->renewalService->processRenewal($this->customer, $this->connection, $payload, $this->admin->id);
        $ren2 = $this->renewalService->processRenewal($this->customer, $this->connection, $payload, $this->admin->id);

        $this->assertEquals($ren1->id, $ren2->id);
        $this->assertEquals(1, Renewal::count());
    }
}
