<?php

namespace Tests\Feature;

use App\Jobs\SendAdminPaymentNotificationJob;
use App\Models\Connection;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerPackage;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\BillingService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminPaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected User $staffUser;
    protected Customer $customer;
    protected Connection $connection;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SmsDefaultsSeeder::class);

        // Admin User
        $this->adminUser = User::where('email', 'admin@pirgachainternet.com')->first();

        // Staff User
        $this->staffUser = User::where('email', 'staff@pirgachainternet.com')->first();

        // Package
        $this->package = Package::create([
            'name' => 'Fiber 15 Mbps',
            'code' => 'PKG-15M',
            'speed_mbps' => 15,
            'is_active' => true,
        ]);
        PackagePrice::create([
            'package_id' => $this->package->id,
            'price' => 750.00,
            'validity_days' => 30,
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
        ]);

        // Customer User & CRM Profile
        $this->customerUser = User::factory()->create([
            'name' => 'Customer Rashed',
            'email' => 'customer@test.com',
            'phone' => '01799887766',
        ]);
        $this->customerUser->roles()->attach(Role::where('slug', 'customer')->first());

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_code' => 'CUST-00999',
            'name' => 'Customer Rashed',
            'status' => 'active',
            'billing_type' => 'prepaid',
            'join_date' => now()->toDateString(),
            'balance' => 0.00,
        ]);

        CustomerContact::create([
            'customer_id' => $this->customer->id,
            'contact_type' => 'primary',
            'phone' => '01799887766',
            'email' => 'customer@test.com',
        ]);

        CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'address_type' => 'installation',
            'full_address' => 'Pirgacha Bazar, Rangpur',
        ]);

        $this->connection = Connection::create([
            'customer_id' => $this->customer->id,
            'connection_code' => 'CON-00999',
            'current_package_id' => $this->package->id,
            'expiry_date' => now()->addDays(5)->toDateString(),
            'status' => 'active',
        ]);

        CustomerPackage::create([
            'customer_id' => $this->customer->id,
            'connection_id' => $this->connection->id,
            'package_id' => $this->package->id,
            'actual_price' => 750.00,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ]);
    }

    public function test_customer_portal_renewal_dispatches_admin_payment_notification_job(): void
    {
        Queue::fake([SendAdminPaymentNotificationJob::class]);

        $response = $this->actingAs($this->customerUser)->post(route('account.renewal.store'), [
            'validity_days' => 30,
            'payment_method' => 'bkash',
            'reference' => 'BKASH-TRX-998877',
        ]);

        $response->assertRedirect(route('account.dashboard'));

        Queue::assertPushed(SendAdminPaymentNotificationJob::class, function ($job) {
            return $job->initiatedBy === $this->customerUser->id;
        });
    }

    public function test_staff_payment_collection_dispatches_admin_payment_notification_job(): void
    {
        Queue::fake([SendAdminPaymentNotificationJob::class]);

        $response = $this->actingAs($this->staffUser)->post(route('customers.pay', $this->customer), [
            'amount' => 750.00,
            'payment_method' => 'cash',
            'reference' => 'HAND-CASH-001',
        ]);

        $response->assertRedirect(route('staff.dashboard'));

        Queue::assertPushed(SendAdminPaymentNotificationJob::class, function ($job) {
            return $job->initiatedBy === $this->staffUser->id;
        });
    }

    public function test_send_admin_payment_notification_job_creates_sms_log_to_admin_phone(): void
    {
        // 1. Create a payment
        $billingService = app(BillingService::class);
        $payment = $billingService->collectPayment($this->customer, [
            'amount' => 750.00,
            'payment_method' => 'bKash Merchant',
            'reference' => 'BKASH-TRX-112233',
        ], $this->customerUser->id);

        // 2. Execute job directly
        $job = new SendAdminPaymentNotificationJob($payment->id, $this->customerUser->id);
        $job->handle(app(SmsService::class));

        // 3. Assert SMS was logged for admin phone (01711000000)
        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '01711000000',
            'customer_id' => $this->customer->id,
            'status' => 'sent',
            'entity_type' => Payment::class,
            'entity_id' => $payment->id,
        ]);

        $smsLog = SmsLog::where('recipient', '01711000000')->latest('id')->first();
        $this->assertStringContainsString('নতুন পেমেন্ট পাওয়া গেছে', $smsLog->message);
        $this->assertStringContainsString('750.00', $smsLog->message);
        $this->assertStringContainsString('BKASH-TRX-112233', $smsLog->message);
    }

    public function test_admin_api_live_counts_returns_accurate_pending_payments_and_latest_payment(): void
    {
        // Staff collects payment (becomes pending)
        $billingService = app(BillingService::class);
        $payment = $billingService->collectPayment($this->customer, [
            'amount' => 500.00,
            'payment_method' => 'Nagad',
            'reference' => 'NAGAD-9988',
        ], $this->staffUser->id);

        $this->assertEquals('pending', $payment->status);

        $response = $this->actingAs($this->adminUser)->get(route('admin.api.live-counts'));

        $response->assertOk()
            ->assertJson([
                'pending_payments_count' => 1,
                'latest_payment' => [
                    'id' => $payment->id,
                    'amount' => 500.00,
                    'payment_method' => 'Nagad',
                    'reference' => 'NAGAD-9988',
                    'status' => 'pending',
                    'customer_name' => 'Customer Rashed',
                    'customer_code' => 'CUST-00999',
                ],
            ]);
    }

    public function test_custom_admin_notification_phone_setting_is_respected(): void
    {
        Setting::set('admin_notification_phone', '01899001122', 'website_settings');

        $billingService = app(BillingService::class);
        $payment = $billingService->collectPayment($this->customer, [
            'amount' => 1200.00,
            'payment_method' => 'Bangla QR',
            'reference' => 'QR-554433',
        ], $this->customerUser->id);

        $job = new SendAdminPaymentNotificationJob($payment->id, $this->customerUser->id);
        $job->handle(app(SmsService::class));

        $this->assertDatabaseHas('sms_logs', [
            'recipient' => '01899001122',
            'entity_type' => Payment::class,
            'entity_id' => $payment->id,
            'status' => 'sent',
        ]);
    }
}
