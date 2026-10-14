<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\SmsService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAdminPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $paymentId,
        public ?int $initiatedBy = null
    ) {}

    /**
     * Execute the job to notify system admin via SMS.
     */
    public function handle(SmsService $smsService): void
    {
        try {
            $payment = Payment::with(['customer.primaryContact'])->find($this->paymentId);
            if (!$payment) {
                return;
            }

            // 1. Resolve Admin notification phone number(s)
            $adminPhone = Setting::get('admin_notification_phone');
            if (empty($adminPhone)) {
                $adminUser = User::whereHas('roles', fn($q) => $q->where('slug', 'admin'))
                    ->whereNotNull('phone')
                    ->first();
                $adminPhone = $adminUser?->phone ?? '01711000000';
            }

            if (empty($adminPhone)) {
                return;
            }

            // 2. Resolve or create SMS template for admin payment alert
            $template = SmsTemplate::firstOrCreate(
                ['code' => 'admin_payment_alert'],
                [
                    'name' => 'Admin Payment Alert',
                    'template' => '[Pirgacha Internet] নতুন পেমেন্ট পাওয়া গেছে! কাস্টমার: {name} ({customer_code}), পরিমাণ: ৳{amount}, মেথড: {payment_method}, TrxID: {reference}. পেমেন্ট নং: {payment_number}.',
                    'is_auto_enabled' => true,
                ]
            );

            if (!$template->is_auto_enabled) {
                return;
            }

            // 3. Interpolate dynamic variables
            $variables = [
                'name' => $payment->customer?->name ?? 'Customer',
                'customer_code' => $payment->customer?->customer_code ?? 'N/A',
                'amount' => number_format((float) $payment->amount, 2),
                'payment_method' => ucfirst($payment->payment_method ?? 'Cash/Online'),
                'reference' => $payment->reference ?: 'None',
                'payment_number' => $payment->payment_number,
                'status' => $payment->status === 'pending' ? 'অপেক্ষমাণ (Pending Approval)' : 'পরিশোধিত (Completed)',
                'date' => now()->format('d/m/Y h:i A'),
            ];

            $message = $smsService->parseTemplate($template->template, $variables);

            // 4. Split comma-separated admin phone numbers if multiple configured
            $phoneList = array_filter(array_map('trim', explode(',', $adminPhone)));

            foreach ($phoneList as $phone) {
                if (empty($phone)) {
                    continue;
                }

                // Prevent duplicate SMS alert for the same payment transaction
                $alreadySent = SmsLog::where('template_id', $template->id)
                    ->where('recipient', $phone)
                    ->where('entity_type', Payment::class)
                    ->where('entity_id', $payment->id)
                    ->where('status', 'sent')
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $smsService->sendSms(
                    $phone,
                    $message,
                    $this->initiatedBy,
                    $payment->customer_id,
                    $template->id,
                    Payment::class,
                    $payment->id
                );
            }
        } catch (Exception $e) {
            Log::error("SendAdminPaymentNotificationJob error for payment {$this->paymentId}: " . $e->getMessage());
        }
    }
}
