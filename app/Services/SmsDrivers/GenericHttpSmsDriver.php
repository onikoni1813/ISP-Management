<?php

namespace App\Services\SmsDrivers;

use App\Contracts\SmsGatewayInterface;
use App\Models\SmsGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenericHttpSmsDriver implements SmsGatewayInterface
{
    /**
     * Send an SMS via HTTP GET or POST request to BD SMS aggregator endpoints (Greenweb, BulkSMSBD, AlphaSMS).
     */
    public function send(string $recipient, string $message, SmsGateway $gateway): array
    {
        try {
            if (!$gateway->api_url) {
                throw new Exception('SMS Gateway API URL is not configured.');
            }

            // Clean recipient number (ensure 11 digits BD or 880 prefix)
            $phone = preg_replace('/[^0-9]/', '', $recipient);
            if (strlen($phone) === 10 && str_starts_with($phone, '1')) {
                $phone = '0' . $phone;
            }

            $payload = [
                'token' => $gateway->api_key,
                'api_key' => $gateway->api_key,
                'to' => $phone,
                'message' => $message,
                'sender_id' => $gateway->sender_id,
            ];

            if (!empty($gateway->extra_params)) {
                $payload = array_merge($payload, $gateway->extra_params);
            }

            $response = Http::timeout(10)->post($gateway->api_url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => 'HTTP-' . uniqid(),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body(),
            ];
        } catch (Exception $e) {
            Log::error("SMS Gateway Error: " . $e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
