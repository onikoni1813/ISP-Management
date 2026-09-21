<?php

namespace Database\Seeders;

use App\Models\SmsGateway;
use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsDefaultsSeeder extends Seeder
{
    /**
     * Seed initial SMS templates and default log gateway.
     */
    public function run(): void
    {
        // 1. Default Gateways
        SmsGateway::firstOrCreate(
            ['driver' => 'log'],
            [
                'name' => 'System Log (Testing)',
                'is_active' => true,
            ]
        );

        SmsGateway::firstOrCreate(
            ['driver' => 'bdbulksms'],
            [
                'name' => 'BDBulkSMS (bdbulksms.net / Greenweb)',
                'api_url' => 'https://api.bdbulksms.net/api.php',
                'api_key' => null,
                'is_active' => false,
            ]
        );

        // 2. Standard Master Plan SMS Templates
        $templates = [
            [
                'name' => 'Payment Received',
                'code' => 'payment_received',
                'template' => 'Dear {name} ({customer_code}), received Tk {amount} for your internet bill. Current due: Tk {due}. Expiry: {expiry_date}. Thank you, Pirgacha Internet.',
                'is_auto_enabled' => true,
            ],
            [
                'name' => 'Package Renewal',
                'code' => 'renewal_completed',
                'template' => 'Dear {name}, your package {package} has been successfully renewed. Your new connection validity expires on {expiry_date}. - Pirgacha Internet',
                'is_auto_enabled' => true,
            ],
            [
                'name' => 'Expiry Warning',
                'code' => 'expiry_warning',
                'template' => 'Dear {name} ({customer_code}), your internet connection will expire on {expiry_date}. Please pay bill Tk {due} to avoid disconnection. - Pirgacha Internet',
                'is_auto_enabled' => true,
            ],
            [
                'name' => 'Connection Expired',
                'code' => 'expired',
                'template' => 'Dear {name}, your internet subscription expired on {expiry_date}. Please clear pending dues to restore your connection immediately. - Pirgacha Internet',
                'is_auto_enabled' => true,
            ],
            [
                'name' => 'Complaint Created',
                'code' => 'complaint_created',
                'template' => 'Dear {name}, your support complaint #{complaint_number} has been logged. Our technician will resolve it shortly. - Pirgacha Internet',
                'is_auto_enabled' => true,
            ],
            [
                'name' => 'Complaint Resolved',
                'code' => 'complaint_resolved',
                'template' => 'Dear {name}, your support ticket #{complaint_number} has been resolved. If you still face issues, please call us. - Pirgacha Internet',
                'is_auto_enabled' => true,
            ],
            [
                'name' => 'PPPoE Credentials & Login Info',
                'code' => 'pppoe_credentials',
                'template' => 'Dear {name}, your Pirgacha Internet account is ready. PPPoE User: {pppoe_username}, Pass: {pppoe_password}. Login portal: {login_url}',
                'is_auto_enabled' => true,
            ],
        ];

        foreach ($templates as $tmpl) {
            SmsTemplate::firstOrCreate(['code' => $tmpl['code']], $tmpl);
        }
    }
}
