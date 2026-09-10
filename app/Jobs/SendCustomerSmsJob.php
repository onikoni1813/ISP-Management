<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\SmsService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCustomerSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $templateCode,
        public int $customerId,
        public array $variables = [],
        public ?int $userId = null,
        public ?string $entityType = null,
        public ?int $entityId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        try {
            $customer = Customer::find($this->customerId);
            if (!$customer) {
                return;
            }

            $smsService->sendByTemplate(
                $this->templateCode,
                $customer,
                $this->variables,
                $this->userId,
                $this->entityType,
                $this->entityId
            );
        } catch (Exception $e) {
            Log::error("SendCustomerSmsJob error for customer {$this->customerId}: " . $e->getMessage());
        }
    }
}
