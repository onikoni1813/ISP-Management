<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Role;
use App\Models\SmsGateway;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\BillingService;
use App\Services\ComplaintService;
use App\Services\RenewalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomatedSmsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Customer $customer;
    protected Connection $connection;
    protected Package $package;
    protected SmsGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->gateway = SmsGateway::create([
            'name' => 'Automated Log Gateway',
            'driver' => 'log',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUST-000500',
            'name' => 'Kamal Hossain',
            'status' => 'active',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
        ]);

        CustomerContact::create([
            'customer_id' => $this->customer->id,
            'contact_type' => 'primary',
            'phone' => '01711223344',
        ]);

        $this->package = Package::create([
            'name' => '15 Mbps Blast',
            'code' => 'PKG-15M',
            'speed_mbps' => 15,
            'is_active' => true,
        ]);

        PackagePrice::create([
            'package_id' => $this->package->id,
            'price' => 800.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->connection = Connection::create([
            'customer_id' => $this->customer->id,
            'connection_code' => 'CON-000500',
            'current_package_id' => $this->package->id,
            'expiry_date' => now()->addDays(3)->toDateString(),
            'status' => 'active',
        ]);

        // Seed default templates
        $this->seed(\Database\Seeders\SmsDefaultsSeeder::class);
    }

    public function test_automated_payment_receipt_sms_dispatched_on_collection(): void
    {
        $billingService = app(BillingService::class);

        $billingService->collectPayment($this->customer, [
            'amount' => 800.00,
            'payment_method' => 'cash',
        ], $this->admin->id);

        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '01711223344',
            'status' => 'sent',
            'customer_id' => $this->customer->id,
            'entity_type' => \App\Models\Payment::class,
        ]);
    }

    public function test_automated_renewal_sms_dispatched_on_renew(): void
    {
        $renewalService = app(RenewalService::class);

        $renewalService->processRenewal($this->customer, $this->connection, [
            'validity_days' => 30,
            'package_id' => $this->package->id,
        ], $this->admin->id);

        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '01711223344',
            'status' => 'sent',
            'customer_id' => $this->customer->id,
            'entity_type' => \App\Models\Renewal::class,
        ]);
    }

    public function test_automated_complaint_sms_dispatched_on_create_and_resolve(): void
    {
        $complaintService = app(ComplaintService::class);

        // 1. Create Complaint
        $complaint = $complaintService->createComplaint($this->customer, [
            'subject' => 'Fiber wire cut near market',
            'description' => 'LOS red light blinking',
        ], $this->admin->id);

        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '01711223344',
            'status' => 'sent',
            'customer_id' => $this->customer->id,
            'entity_type' => \App\Models\Complaint::class,
            'entity_id' => $complaint->id,
        ]);

        // 2. Resolve Complaint
        $complaintService->updateStatus($complaint, 'resolved', $this->admin->id, 'Replaced patch cord');

        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '01711223344',
            'status' => 'sent',
            'customer_id' => $this->customer->id,
            'entity_type' => \App\Models\Complaint::class,
            'entity_id' => $complaint->id,
        ]);
    }

    public function test_scheduled_expiry_command_sends_warning_and_prevents_duplicate(): void
    {
        // Connection expires in 3 days
        $this->artisan('isp:send-expiry-sms')
            ->expectsOutputToContain('Sent 1 expiry warnings')
            ->assertExitCode(0);

        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '01711223344',
            'status' => 'sent',
            'customer_id' => $this->customer->id,
            'entity_type' => Connection::class,
            'entity_id' => $this->connection->id,
        ]);

        // Running it a second time today must not send duplicate SMS
        $this->artisan('isp:send-expiry-sms')
            ->expectsOutputToContain('Sent 0 expiry warnings')
            ->assertExitCode(0);
    }
}
