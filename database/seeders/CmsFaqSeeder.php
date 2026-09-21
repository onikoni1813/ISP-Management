<?php

namespace Database\Seeders;

use App\Models\CmsFaq;
use Illuminate\Database\Seeder;

class CmsFaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            // General
            [
                'question' => 'How long does a new connection setup take?',
                'answer' => 'Once your application is received, our field technicians conduct a signal check and complete optical fiber drop cable wiring along with ONU configuration within 24 hours in all covered areas.',
                'category' => 'General',
                'order' => 1,
                'is_published' => true,
            ],
            [
                'question' => 'Is there any data limit, cap or Fair Usage Policy (FUP)?',
                'answer' => 'No. All Pirgacha Internet residential and corporate optical packages come with unlimited data with zero throttling or speed drops, allowing uninterrupted streaming, gaming, and downloading.',
                'category' => 'General',
                'order' => 2,
                'is_published' => true,
            ],
            [
                'question' => 'Can I upgrade or downgrade my internet package anytime?',
                'answer' => 'Yes, you can easily change your speed package at any time from your customer self-service portal or by contacting our hotline. Package upgrades can take effect immediately or on your next billing cycle.',
                'category' => 'General',
                'order' => 3,
                'is_published' => true,
            ],

            // Setup & Connection
            [
                'question' => 'What equipment and hardware are provided with the connection?',
                'answer' => 'We provide an Optical Network Unit (ONU) programmed with your secure PPPoE subscriber credentials, optical fiber drop cable up to your router, optical patch cords, and secure termination.',
                'category' => 'Setup & Connection',
                'order' => 4,
                'is_published' => true,
            ],
            [
                'question' => 'Do you provide a Wi-Fi router with the optical connection?',
                'answer' => 'We configure your existing Wi-Fi router free of charge. If you need a new router, we offer high-gain dual-band (2.4GHz + 5GHz) gigabit routers tested for maximum optical throughput at subsidized rates.',
                'category' => 'Setup & Connection',
                'order' => 5,
                'is_published' => true,
            ],
            [
                'question' => 'How can I apply for a new internet connection in Pirgacha?',
                'answer' => 'You can submit your application directly through our website by clicking "Get Connected" or visiting our Contact page. Alternatively, call our 24/7 hotline and a technician will survey your location.',
                'category' => 'Setup & Connection',
                'order' => 6,
                'is_published' => true,
            ],

            // Billing & Payments
            [
                'question' => 'How can I pay my monthly internet bill?',
                'answer' => 'You can pay instantly online 24/7 via bKash, Nagad, and Rocket through your subscriber portal dashboard. Cash payments are also accepted at our central office or through authorized field collection agents with digital receipts.',
                'category' => 'Billing & Payments',
                'order' => 7,
                'is_published' => true,
            ],
            [
                'question' => 'When is my monthly bill due and how will I receive the invoice?',
                'answer' => 'Invoices are generated on your monthly billing cycle date. You will receive an SMS reminder on your registered mobile number with your outstanding balance, due date, and instant online payment link.',
                'category' => 'Billing & Payments',
                'order' => 8,
                'is_published' => true,
            ],
            [
                'question' => 'What happens if my connection is disconnected due to late payment?',
                'answer' => 'If your line is temporarily suspended due to pending dues, you do not need to call support. Simply clear your due invoice online via bKash or Nagad from your portal, and the system restores your optical connection automatically within seconds.',
                'category' => 'Billing & Payments',
                'order' => 9,
                'is_published' => true,
            ],

            // Troubleshooting
            [
                'question' => 'What should I do if the LOS light on the ONU is blinking red?',
                'answer' => 'A blinking red LOS (Loss of Signal) light means the optical fiber cable is disconnected, bent, or broken physically outside. Do not pull the fiber wire. Please submit a support ticket via your portal or dial our emergency NOC hotline immediately for a technician dispatch.',
                'category' => 'Troubleshooting',
                'order' => 10,
                'is_published' => true,
            ],
            [
                'question' => 'Why is my internet slow on Wi-Fi even though the line is active?',
                'answer' => 'First, try restarting your Wi-Fi router by turning off power for 30 seconds. Ensure the router is placed in an elevated, central location away from thick concrete walls or microwave ovens. For peak speeds, connect via 5GHz Wi-Fi or directly via Ethernet LAN cable.',
                'category' => 'Troubleshooting',
                'order' => 11,
                'is_published' => true,
            ],
            [
                'question' => 'How do I raise a complaint or support ticket?',
                'answer' => 'Log in to your subscriber portal and go to "Support / Complaints" to submit a ticket with real-time tracking, or call our NOC hotline at any hour. Our average field response time in Pirgacha is under 60 minutes.',
                'category' => 'Troubleshooting',
                'order' => 12,
                'is_published' => true,
            ],

            // Technical
            [
                'question' => 'Do you provide BDIX and local peering speed?',
                'answer' => 'Yes! All Pirgacha Internet connections feature ultra-low latency BDIX peering (<5ms) and high-speed multi-gigabit routing for YouTube, Facebook CDN, Google Cache, BDIX FTP servers, and local gaming servers.',
                'category' => 'Technical',
                'order' => 13,
                'is_published' => true,
            ],
            [
                'question' => 'Can I get a Real IP (Public Static IP) address for CCTV or servers?',
                'answer' => 'Yes, dedicated Real Public Static IPv4 addresses are available upon request for CCTV camera streaming, corporate VPNs, port forwarding, and local server hosting for a nominal monthly fee.',
                'category' => 'Technical',
                'order' => 14,
                'is_published' => true,
            ],
        ];

        foreach ($faqs as $data) {
            CmsFaq::firstOrCreate(
                ['question' => $data['question']],
                $data
            );
        }
    }
}
