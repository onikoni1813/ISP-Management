<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Package;
use App\Models\PackagePrice;
use Illuminate\Database\Seeder;

class CoreIspSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


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
    }
}

