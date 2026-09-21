<?php

namespace App\Console\Commands;

use App\Models\Connection;
use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAutomatedExpirySmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'isp:send-expiry-sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated expiry reminders (3 days before, 1 day before, today) and expired notices';

    /**
     * Execute the console command.
     */
    public function handle(SmsService $smsService): int
    {
        $this->info('Starting automated ISP expiry & expired SMS notifications...');

        $today = Carbon::today();
        $inThreeDays = Carbon::today()->addDays(3)->toDateString();
        $inOneDay = Carbon::today()->addDays(1)->toDateString();
        $todayStr = Carbon::today()->toDateString();

        $remindersCount = 0;
        $expiredCount = 0;

        // 1. Expiry Warning: 3 Days, 1 Day, and Today
        $expiringConnections = Connection::with(['customer.primaryContact', 'currentPackage'])
            ->where(function ($query) use ($inThreeDays, $inOneDay, $todayStr) {
                $query->whereDate('expiry_date', $inThreeDays)
                    ->orWhereDate('expiry_date', $inOneDay)
                    ->orWhereDate('expiry_date', $todayStr);
            })
            ->where('status', 'active')
            ->get();

        $warningTemplate = SmsTemplate::where('code', 'expiry_warning')->first();

        if ($warningTemplate && $warningTemplate->is_auto_enabled) {
            foreach ($expiringConnections as $conn) {
                $customer = $conn->customer;
                if (!$customer) continue;

                // Check if already sent today for this connection
                $alreadySent = SmsLog::where('template_id', $warningTemplate->id)
                    ->where('customer_id', $customer->id)
                    ->where('entity_type', Connection::class)
                    ->where('entity_id', $conn->id)
                    ->whereDate('created_at', $todayStr)
                    ->exists();

                $sent = $smsService->sendByTemplate(
                    'expiry_warning',
                    $customer,
                    [
                        'expiry_date' => $conn->expiry_date?->toDateString(),
                        'package' => $conn->currentPackage?->name ?? 'Internet Package',
                        'due' => number_format((float) max(0, -$customer->balance), 2),
                    ],
                    null,
                    Connection::class,
                    $conn->id
                );

                if ($sent) {
                    $remindersCount++;
                }
            }
        }

        // 2. Expired Notification: Expiry date is yesterday or earlier, customer still expired
        $expiredTemplate = SmsTemplate::where('code', 'expired')->first();

        if ($expiredTemplate && $expiredTemplate->is_auto_enabled) {
            $yesterday = Carbon::yesterday()->toDateString();

            $expiredConnections = Connection::with(['customer.primaryContact', 'currentPackage'])
                ->where('expiry_date', $yesterday)
                ->get();

            foreach ($expiredConnections as $conn) {
                $customer = $conn->customer;
                if (!$customer) continue;

                // Update connection status to expired if not already
                if ($conn->status !== 'expired') {
                    $conn->update(['status' => 'expired']);
                }

                $alreadySent = SmsLog::where('template_id', $expiredTemplate->id)
                    ->where('customer_id', $customer->id)
                    ->where('entity_type', Connection::class)
                    ->where('entity_id', $conn->id)
                    ->exists();

                if ($alreadySent) continue;

                $smsService->sendByTemplate(
                    'expired',
                    $customer,
                    [
                        'expiry_date' => $conn->expiry_date?->toDateString(),
                        'package' => $conn->currentPackage?->name ?? 'Internet Package',
                        'due' => number_format((float) max(0, -$customer->balance), 2),
                    ],
                    null,
                    Connection::class,
                    $conn->id
                );

                $expiredCount++;
            }
        }

        $this->info("Completed. Sent {$remindersCount} expiry warnings and {$expiredCount} expired notices.");

        return Command::SUCCESS;
    }
}
