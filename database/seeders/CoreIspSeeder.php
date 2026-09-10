<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerPackage;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\PppoeCredential;
use App\Models\User;
use Illuminate\Database\Seeder;

class CoreIspSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@pirgachainternet.com')->first();
        $customerUser = User::where('email', 'customer@pirgachainternet.com')->first();

        // 1. Seed Hierarchical Areas
        $mainArea = Area::firstOrCreate(
            ['code' => 'AREA-PIRGACHA'],
            ['name' => 'Pirgacha Sadar', 'status' => 'active', 'description' => 'Main town center']
        );

        $subZone1 = Area::firstOrCreate(
            ['code' => 'ZONE-BAZAR'],
            ['parent_id' => $mainArea->id, 'name' => 'Pirgacha Bazar', 'status' => 'active', 'description' => 'Commercial center']
        );

        $subZone2 = Area::firstOrCreate(
            ['code' => 'ZONE-COLLEGE'],
            ['parent_id' => $mainArea->id, 'name' => 'College Road', 'status' => 'active', 'description' => 'Residential and student zone']
        );

        // 2. Seed Packages & Historical Prices
        $packagesData = [
            ['code' => 'PKG-10M', 'name' => 'Standard 10 Mbps', 'speed' => 10, 'price' => 500.00],
            ['code' => 'PKG-15M', 'name' => 'Super 15 Mbps', 'speed' => 15, 'price' => 700.00],
            ['code' => 'PKG-20M', 'name' => 'Ultra 20 Mbps', 'speed' => 20, 'price' => 900.00],
            ['code' => 'PKG-30M', 'name' => 'Premium 30 Mbps', 'speed' => 30, 'price' => 1200.00],
        ];

        $seededPackages = [];

        foreach ($packagesData as $pkg) {
            $package = Package::firstOrCreate(
                ['code' => $pkg['code']],
                [
                    'name' => $pkg['name'],
                    'speed_mbps' => $pkg['speed'],
                    'description' => "Fast optical fiber {$pkg['speed']} Mbps internet",
                    'status' => 'active',
                ]
            );

            // Versioned active price
            PackagePrice::firstOrCreate(
                ['package_id' => $package->id, 'price' => $pkg['price']],
                [
                    'validity_days' => 30,
                    'effective_from' => now()->subMonths(3),
                    'status' => 'active',
                ]
            );

            $seededPackages[$pkg['code']] = $package;
        }

        // 3. Seed Sample Customers with Separation of Connection & PPPoE
        $sampleCustomer = Customer::firstOrCreate(
            ['customer_code' => 'CUST-000001'],
            [
                'user_id' => $customerUser?->id,
                'area_id' => $subZone1->id,
                'name' => 'Karim Customer',
                'status' => 'active',
                'join_date' => now()->subMonths(2)->toDateString(),
                'billing_day' => 1,
                'balance' => 0.00,
                'created_by' => $adminUser?->id,
                'notes' => 'VIP customer, fiber connection',
            ]
        );

        // Contact
        CustomerContact::firstOrCreate(
            ['customer_id' => $sampleCustomer->id, 'phone' => '01733000000'],
            [
                'contact_type' => 'primary',
                'name' => 'Karim Customer',
                'email' => 'customer@pirgachainternet.com',
            ]
        );

        // Address
        CustomerAddress::firstOrCreate(
            ['customer_id' => $sampleCustomer->id, 'address_type' => 'installation'],
            [
                'house_no' => '12',
                'road_no' => '2',
                'village_or_area' => 'Pirgacha Bazar',
                'post_office' => 'Pirgacha',
                'full_address' => 'House 12, Road 2, Pirgacha Bazar, Rangpur',
            ]
        );

        // Connection
        $standardPackage = $seededPackages['PKG-10M'];
        $connection = Connection::firstOrCreate(
            ['connection_code' => 'CON-000001'],
            [
                'customer_id' => $sampleCustomer->id,
                'area_id' => $subZone1->id,
                'current_package_id' => $standardPackage->id,
                'protocol' => 'pppoe',
                'ip_address' => '192.168.10.25',
                'mac_address' => 'FC:EC:DA:11:22:33',
                'router_model' => 'TP-Link Archer C6',
                'status' => 'active',
                'installation_date' => now()->subMonths(2)->toDateString(),
                'expiry_date' => now()->addDays(20)->toDateString(),
            ]
        );

        // Encrypted PPPoE Credentials
        PppoeCredential::firstOrCreate(
            ['connection_id' => $connection->id],
            [
                'username' => 'karim_pirgacha',
                'password' => 'secret_pppoe_pass_2026', // Stored encrypted automatically via casts
                'service_name' => 'pirgacha_pppoe',
                'status' => 'active',
            ]
        );

        // Customer Package History
        CustomerPackage::firstOrCreate(
            ['customer_id' => $sampleCustomer->id, 'connection_id' => $connection->id],
            [
                'package_id' => $standardPackage->id,
                'actual_price' => 500.00,
                'start_date' => now()->subMonths(2)->toDateString(),
                'assigned_by' => $adminUser?->id,
                'status' => 'active',
                'remarks' => 'Initial installation package',
            ]
        );
    }
}
