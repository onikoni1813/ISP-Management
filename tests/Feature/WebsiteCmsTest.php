<?php

namespace Tests\Feature;

use App\Models\CmsBanner;
use App\Models\CmsFaq;
use App\Models\CmsNotice;
use App\Models\CmsPage;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteCmsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);

        $managePerm = Permission::create([
            'name' => 'Manage website',
            'slug' => 'website.manage',
            'group' => 'website'
        ]);

        $adminRole->permissions()->attach($managePerm);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);
    }

    public function test_admin_can_view_cms_index_hub(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.cms.index'));

        $response->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('Admin/Cms/Index')
                ->has('pagesCount')
                ->has('bannersCount')
                ->has('faqsCount')
                ->has('noticesCount')
            );
    }

    public function test_unauthorized_user_cannot_manage_cms(): void
    {
        $this->actingAs($this->staff)
            ->get(route('admin.cms.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_update_toggle_and_delete_cms_page(): void
    {
        // 1. Create Page
        $payload = [
            'slug' => 'terms-of-service',
            'title' => 'Terms of Service',
            'content' => 'Official service agreement terms.',
            'seo_title' => 'Terms of Service - Pirgacha Internet',
            'seo_description' => 'Legal terms governing subscriber connection.',
            'is_published' => true,
            'order' => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.cms.pages.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'terms-of-service',
            'title' => 'Terms of Service',
            'is_published' => true,
        ]);

        $page = CmsPage::where('slug', 'terms-of-service')->first();

        // 2. Public can view custom page
        $this->get(route('page.custom', 'terms-of-service'))
            ->assertOk()
            ->assertInertia(fn($p) => $p
                ->component('Website/CustomPage')
                ->where('page.title', 'Terms of Service')
            );

        // 3. Toggle Publish
        $this->actingAs($this->admin)
            ->post(route('admin.cms.pages.toggle', $page->id))
            ->assertRedirect();

        $this->assertFalse($page->fresh()->is_published);

        // Custom page 404 when unpublished
        $this->get(route('page.custom', 'terms-of-service'))
            ->assertNotFound();

        // 4. Update Page
        $this->actingAs($this->admin)
            ->patch(route('admin.cms.pages.update', $page->id), [
                'slug' => 'terms-of-service',
                'title' => 'Updated Terms',
                'content' => 'Revised terms.',
                'is_published' => true,
                'order' => 2,
            ])
            ->assertRedirect();

        $this->assertEquals('Updated Terms', $page->fresh()->title);

        // 5. Delete Page
        $this->actingAs($this->admin)
            ->delete(route('admin.cms.pages.destroy', $page->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('cms_pages', ['id' => $page->id]);
    }

    public function test_admin_can_manage_banners_and_renders_on_homepage(): void
    {
        $bannerPayload = [
            'title' => 'Gigabit Speed Winter Festival Offer',
            'subtitle' => 'Get zero installation fees this month.',
            'badge_text' => 'Winter Promo',
            'button_text' => 'Claim Offer',
            'button_url' => '#apply',
            'is_active' => true,
            'order' => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.cms.banners.store'), $bannerPayload)
            ->assertRedirect();

        $this->assertDatabaseHas('cms_banners', [
            'title' => 'Gigabit Speed Winter Festival Offer',
            'is_active' => true,
        ]);

        // Verify public home page receives banner
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn($p) => $p
                ->component('Website/Home')
                ->has('banners', 1)
                ->where('banners.0.badge_text', 'Winter Promo')
            );
    }

    public function test_admin_can_manage_faqs_and_renders_on_faq_page(): void
    {
        $faqPayload = [
            'question' => 'How can I pay my bill using bKash?',
            'answer' => 'Use the subscriber self service portal to execute instant bKash payment.',
            'category' => 'Billing & Payments',
            'order' => 1,
            'is_published' => true,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.cms.faqs.store'), $faqPayload)
            ->assertRedirect();

        $this->assertDatabaseHas('cms_faqs', [
            'question' => 'How can I pay my bill using bKash?',
            'category' => 'Billing & Payments',
        ]);

        // Public FAQ page receives active questions
        $this->get(route('faq'))
            ->assertOk()
            ->assertInertia(fn($p) => $p
                ->component('Website/Faq')
                ->has('faqs', 1)
                ->where('faqs.0.category', 'Billing & Payments')
            );
    }

    public function test_admin_can_manage_notices_and_renders_on_notices_page(): void
    {
        $noticePayload = [
            'title' => 'Submarine Cable Emergency Optical Reroute',
            'category' => 'Maintenance',
            'content' => 'Traffic rerouted over terrestrial cross-border link with zero interruption.',
            'published_at' => now()->toDateString(),
            'is_published' => true,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.cms.notices.store'), $noticePayload)
            ->assertRedirect();

        $this->assertDatabaseHas('cms_notices', [
            'title' => 'Submarine Cable Emergency Optical Reroute',
            'category' => 'Maintenance',
        ]);

        // Public Notices page receives notice
        $this->get(route('notices'))
            ->assertOk()
            ->assertInertia(fn($p) => $p
                ->component('Website/Notices')
                ->has('notices', 1)
                ->where('notices.0.title', 'Submarine Cable Emergency Optical Reroute')
            );
    }
}
