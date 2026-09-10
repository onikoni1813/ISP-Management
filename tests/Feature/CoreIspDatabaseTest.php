<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\PppoeCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoreIspDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_hierarchical_areas_can_be_created_and_queried(): void
    {
        $parent = Area::create([
            'name' => 'Pirgacha Sadar',
            'code' => 'AREA-001',
            'status' => 'active',
        ]);

        $child = Area::create([
            'parent_id' => $parent->id,
            'name' => 'Pirgacha Bazar',
            'code' => 'ZONE-001',
            'status' => 'active',
        ]);

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_packages_support_price_versioning(): void
    {
        $package = Package::create([
            'name' => '10 Mbps Fiber',
            'code' => 'PKG-10M',
            'speed_mbps' => 10,
        ]);

        // Historical price
        $oldPrice = PackagePrice::create([
            'package_id' => $package->id,
            'price' => 450.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonths(6),
            'effective_to' => now()->subMonths(1),
            'status' => 'superseded',
        ]);

        // Active price
        $newPrice = PackagePrice::create([
            'package_id' => $package->id,
            'price' => 500.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonths(1),
            'status' => 'active',
        ]);

        $this->assertCount(2, $package->prices);
        $this->assertEquals('500.00', (string) $package->currentPrice->price);
    }

    public function test_customer_and_connection_are_separate_entities(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-99001',
            'name' => 'Habib Mia',
            'status' => 'active',
            'join_date' => now()->toDateString(),
        ]);

        $package = Package::create([
            'name' => 'Standard',
            'code' => 'PKG-STD',
            'speed_mbps' => 10,
        ]);

        $conn1 = Connection::create([
            'connection_code' => 'CON-001',
            'customer_id' => $customer->id,
            'current_package_id' => $package->id,
            'status' => 'active',
        ]);

        $conn2 = Connection::create([
            'connection_code' => 'CON-002',
            'customer_id' => $customer->id,
            'current_package_id' => $package->id,
            'status' => 'active',
        ]);

        $this->assertCount(2, $customer->connections);
        $this->assertEquals($customer->id, $conn1->customer->id);
    }

    public function test_pppoe_passwords_are_never_stored_in_plain_text(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-77001',
            'name' => 'Jamal Uddin',
            'status' => 'active',
            'join_date' => now()->toDateString(),
        ]);

        $conn = Connection::create([
            'connection_code' => 'CON-77001',
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $credential = PppoeCredential::create([
            'connection_id' => $conn->id,
            'username' => 'jamal_net',
            'password' => 'super_secret_pppoe_123',
        ]);

        // Verify raw database column is encrypted and does NOT contain plain text
        $rawRow = DB::table('pppoe_credentials')->where('id', $credential->id)->first();
        $this->assertNotEquals('super_secret_pppoe_123', $rawRow->password);

        // Verify model decrypts it on authorized retrieval
        $this->assertEquals('super_secret_pppoe_123', $credential->password);
    }
}
