<?php

namespace App\Services\SmsDrivers;

use App\Contracts\SmsGatewayInterface;
use App\Models\SmsGateway;
use Illuminate\Support\Facades\Log;

class LogSmsDriver implements SmsGatewayInterface
{
    /**
     * Send an SMS by writing to Laravel application log (Safe default / testing).
     */
    public function send(string $recipient, string $message, SmsGateway $gateway): array
    {
        Log::info("SMS DISPATCHED via LogDriver to {$recipient}: {$message}");

        return [
            'success' => true,
            'message_id' => 'LOG-' . uniqid(),
            'error' => null,
        ];
    }

    /**
     * Check balance for local log driver.
     */
    public function getBalance(SmsGateway $gateway): array
    {
        return [
            'success' => true,
            'balance' => 'টেস্টিং মোড (সীমাহীন/Log)',
            'error' => null,
        ];
    }
}
