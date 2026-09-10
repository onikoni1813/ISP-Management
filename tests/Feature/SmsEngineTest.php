<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\SmsGateway;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected SmsService $smsService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->smsService = app(SmsService::class);
    }

    public function test_admin_can_view_sms_index_and_logs(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sms.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Sms/Index'));
    }

    public function test_admin_can_send_manual_sms_via_log_driver(): void
    {
        $gateway = SmsGateway::create([
            'name' => 'Test Log Gateway',
            'driver' => 'log',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.sms.send'), [
            'recipient' => '01712345678',
            'message' => 'Network maintenance will be conducted tonight from 2AM to 4AM.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '01712345678',
            'status' => 'sent',
            'gateway_id' => $gateway->id,
            'sent_by' => $this->admin->id,
        ]);
    }

    public function test_template_variable_interpolation(): void
    {
        $template = SmsTemplate::create([
            'name' => 'Payment Received',
            'code' => 'payment_received',
            'template' => 'Dear {name} ({customer_code}), received Tk {amount}. Due: Tk {due}.',
            'is_auto_enabled' => true,
        ]);

        $parsed = $this->smsService->parseTemplate($template->template, [
            'name' => 'Md Faruk',
            'customer_code' => 'CUST-000999',
            'amount' => '800.00',
            'due' => '0.00',
        ]);

        $this->assertEquals('Dear Md Faruk (CUST-000999), received Tk 800.00. Due: Tk 0.00.', $parsed);
    }

    public function test_admin_can_update_sms_template(): void
    {
        $template = SmsTemplate::create([
            'name' => 'Expiry Notice',
            'code' => 'expiry_warning',
            'template' => 'Old template body',
            'is_auto_enabled' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.sms.templates.update', $template->id), [
            'template' => 'New template body for {name}',
            'is_auto_enabled' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('New template body for {name}', $template->fresh()->template);
        $this->assertFalse((bool) $template->fresh()->is_auto_enabled);
    }

    public function test_admin_can_retry_failed_sms(): void
    {
        $gateway = SmsGateway::create([
            'name' => 'Active Test Gateway',
            'driver' => 'log',
            'is_active' => true,
        ]);

        $failedLog = SmsLog::create([
            'recipient' => '01987654321',
            'message' => 'Test message retry',
            'status' => 'failed',
            'error_message' => 'Connection timeout',
            'gateway_id' => $gateway->id,
            'sent_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.sms.retry', $failedLog->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('sent', $failedLog->fresh()->status);
        $this->assertNull($failedLog->fresh()->error_message);
    }
}
