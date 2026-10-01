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

    public function test_bdbulksms_driver_sends_sms_and_checks_balance(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://api.bdbulksms.net/api.php*' => \Illuminate\Support\Facades\Http::response([
                [
                    'status' => 'SENT',
                    'status_code' => '1000',
                    'message_id' => 'BDBULK-TEST-9988',
                ]
            ], 200),
            'https://api.bdbulksms.net/g_api.php*' => \Illuminate\Support\Facades\Http::response([
                'balance' => '150.50',
            ], 200),
        ]);

        $gateway = SmsGateway::create([
            'name' => 'BDBulkSMS Production',
            'driver' => 'bdbulksms',
            'api_url' => 'https://api.bdbulksms.net/api.php',
            'api_key' => 'test_bdbulksms_token_123',
            'sender_id' => 'PirgachaNet',
            'is_active' => true,
        ]);

        $driver = $this->smsService->resolveDriver('bdbulksms');
        $sendResult = $driver->send('01712345678', 'Testing BDBulkSMS Gateway integration', $gateway);

        $this->assertTrue($sendResult['success']);
        $this->assertEquals('BDBULK-TEST-9988', $sendResult['message_id']);

        $balanceResult = $driver->getBalance($gateway);
        $this->assertTrue($balanceResult['success']);
        $this->assertEquals('150.50', $balanceResult['balance']);
    }

    public function test_bulksmsdhaka_driver_sends_sms_and_checks_balance(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://bulksmsdhaka.net/api/sendtext*' => \Illuminate\Support\Facades\Http::response([
                'Status' => '1000',
                'Success' => 'true',
                'Message' => 'Your OTP Send Successful!!',
                'message_id' => 'BSMD-9988',
            ], 200),
            'https://bulksmsdhaka.net/api/getBalance*' => \Illuminate\Support\Facades\Http::response([
                'Balance' => '250.75',
                'Status' => '100',
                'Success' => 'true',
                'Message' => 'Successfully Done.',
            ], 200),
        ]);

        $gateway = SmsGateway::create([
            'name' => 'Bulk SMS Dhaka Live',
            'driver' => 'bulksmsdhaka',
            'api_url' => 'https://bulksmsdhaka.net/api',
            'api_key' => 'test_bulksmsdhaka_key_123',
            'sender_id' => '1234',
            'is_active' => true,
        ]);

        $driver = $this->smsService->resolveDriver('bulksmsdhaka');
        $sendResult = $driver->send('01712345678', 'Testing Bulk SMS Dhaka API', $gateway);

        $this->assertTrue($sendResult['success']);
        $this->assertEquals('BSMD-9988', $sendResult['message_id']);

        $balanceResult = $driver->getBalance($gateway);
        $this->assertTrue($balanceResult['success']);
        $this->assertEquals('250.75', $balanceResult['balance']);
    }

    public function test_admin_can_delete_sms_gateway(): void
    {
        $gateway1 = SmsGateway::create([
            'name' => 'Gateway to Delete',
            'driver' => 'bdbulksms',
            'is_active' => true,
        ]);

        $gateway2 = SmsGateway::create([
            'name' => 'Fallback Gateway',
            'driver' => 'log',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.sms.gateways.destroy', $gateway1->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sms_gateways', ['id' => $gateway1->id]);
        $this->assertTrue((bool)$gateway2->fresh()->is_active);
    }

    public function test_admin_can_fetch_active_sms_balance(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://api.bdbulksms.net/g_api.php*' => \Illuminate\Support\Facades\Http::response([
                'balance' => '500.00',
            ], 200),
        ]);

        $gateway = SmsGateway::create([
            'name' => 'BDBulkSMS Active',
            'driver' => 'bdbulksms',
            'api_url' => 'https://api.bdbulksms.net/api.php',
            'api_key' => 'token_xyz_123',
            'sender_id' => 'PirgachaNet',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.sms.active-balance', ['force' => 1]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'gateway' => [
                'id' => $gateway->id,
                'name' => 'BDBulkSMS Active',
            ],
            'balance' => '500.00',
        ]);
    }

    public function test_admin_can_check_specific_gateway_balance_endpoint(): void
    {
        $logGateway = SmsGateway::create([
            'name' => 'Local Log Gateway',
            'driver' => 'log',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.sms.gateways.balance', $logGateway->id));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }
}


