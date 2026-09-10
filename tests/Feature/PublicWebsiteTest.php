<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Package;
use App\Models\PackagePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected Package $package;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->area = Area::create([
            'name' => 'Pirgacha Sadar Zone',
            'code' => 'PSZ-01',
            'status' => 'active',
        ]);

        $this->package = Package::create([
            'name' => '20 Mbps Ultra Fiber',
            'code' => 'PKG-20M',
            'speed_mbps' => 20,
            'status' => 'active',
        ]);

        PackagePrice::create([
            'package_id' => $this->package->id,
            'price' => 1000.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ]);
    }

    public function test_public_homepage_renders_with_dynamic_packages_and_coverage(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Website/Home')
                ->has('packages', 1)
                ->where('packages.0.name', '20 Mbps Ultra Fiber')
                ->has('coverageAreas', 1)
            );
    }

    public function test_packages_page_renders_packages_from_database(): void
    {
        $response = $this->get(route('packages'));

        $response->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Website/Packages')
                ->has('packages', 1)
            );
    }

    public function test_coverage_page_renders_active_zones(): void
    {
        $response = $this->get(route('coverage'));

        $response->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Website/Coverage')
                ->has('coverageAreas', 1)
                ->where('coverageAreas.0.name', 'Pirgacha Sadar Zone')
            );
    }

    public function test_static_pages_render_successfully(): void
    {
        $this->get(route('about'))->assertOk()->assertInertia(fn($p) => $p->component('Website/About'));
        $this->get(route('faq'))->assertOk()->assertInertia(fn($p) => $p->component('Website/Faq'));
        $this->get(route('notices'))->assertOk()->assertInertia(fn($p) => $p->component('Website/Notices'));
        $this->get(route('contact'))->assertOk()->assertInertia(fn($p) => $p->component('Website/Contact'));
    }

    public function test_online_connection_application_submits_and_creates_customer(): void
    {
        $payload = [
            'name' => 'Anwar Hossain',
            'phone' => '01788776655',
            'email' => 'anwar@gmail.com',
            'area_id' => $this->area->id,
            'package_id' => $this->package->id,
            'address' => 'Near Pirgacha Railway Station',
            'notes' => 'Looking for optical fiber connection for freelancing work.',
        ];

        $response = $this->post(route('apply'), $payload);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'name' => 'Anwar Hossain',
            'area_id' => $this->area->id,
        ]);

        $this->assertDatabaseHas('customer_contacts', [
            'phone' => '01788776655',
        ]);
    }
}
