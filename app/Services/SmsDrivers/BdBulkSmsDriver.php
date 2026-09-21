<?php

namespace App\Services\SmsDrivers;

use App\Contracts\SmsGatewayInterface;
use App\Models\SmsGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BdBulkSmsDriver implements SmsGatewayInterface
{
    /**
     * Official API URL for BDBulkSMS / Greenweb
     * Supports both api.bdbulksms.net and api.greenweb.com.bd
     */
    protected string $defaultApiUrl = 'https://api.bdbulksms.net/api.php';
    protected string $balanceApiUrl = 'https://api.bdbulksms.net/g_api.php';

    /**
     * Send an SMS via BDBulkSMS / Greenweb API.
     * Required parameters: token, to, message
     */
    public function send(string $recipient, string $message, SmsGateway $gateway): array
    {
        try {
            $apiUrl = $gateway->api_url ?: config('services.bdbulksms.api_url', env('BDBULKSMS_API_URL', $this->defaultApiUrl));
            $token = $gateway->api_key ?: config('services.bdbulksms.token', env('BDBULKSMS_TOKEN', env('GREENWEB_SMS_TOKEN')));

            if (empty($token)) {
                throw new Exception('BDBulkSMS API Token is not configured. Please set your token in SMS Gateway settings or in .env as BDBULKSMS_TOKEN.');
            }

            // Normalize phone number (11 digits e.g. 017XXXXXXXX or with 880 prefix)
            $phone = preg_replace('/[^0-9]/', '', $recipient);
            if (strlen($phone) === 10 && str_starts_with($phone, '1')) {
                $phone = '0' . $phone;
            }

            // Ensure JSON response format from BDBulkSMS
            $url = $apiUrl;
            if (!str_contains($url, 'json')) {
                $url = str_contains($url, '?') ? ($url . '&json') : ($url . '?json');
            }

            $payload = [
                'token' => $token,
                'to' => $phone,
                'message' => $message,
            ];

            // Optional masking/sender ID if provided
            if (!empty($gateway->sender_id)) {
                $payload['senderid'] = $gateway->sender_id;
            }

            $response = Http::asForm()->timeout(15)->post($url, $payload);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message_id' => null,
                    'error' => 'HTTP ' . $response->status() . ': ' . $response->body(),
                ];
            }

            $body = $response->body();
            $data = $response->json();

            // Check JSON response structures
            // Format 1: [{"status":"SENT","status_code":"1000","message_id":"123456"}]
            // Format 2: {"status":"SENT","status_code":"1000","message_id":"123456"}
            // Format 3: Raw string starting with "1000" or "Ok"
            if (is_array($data)) {
                $item = isset($data[0]) ? $data[0] : $data;
                $statusCode = (string)($item['status_code'] ?? $item['code'] ?? '');
                $status = strtoupper((string)($item['status'] ?? ''));

                if ($statusCode === '1000' || $status === 'SENT' || $status === 'SUCCESS') {
                    $msgId = $item['message_id'] ?? $item['msg_id'] ?? ('BDBULKSMS-' . uniqid());
                    return [
                        'success' => true,
                        'message_id' => (string)$msgId,
                        'error' => null,
                        'data' => $item,
                    ];
                }

                $errMsg = $item['status'] ?? $item['message'] ?? $item['error'] ?? 'SMS dispatch failed';
                return [
                    'success' => false,
                    'message_id' => null,
                    'error' => "BDBulkSMS [{$statusCode}]: {$errMsg}",
                ];
            }

            // Fallback plain-text response checking
            if (str_contains($body, '1000') || stripos($body, 'Ok') !== false || stripos($body, 'Sent') !== false) {
                return [
                    'success' => true,
                    'message_id' => 'BDBULKSMS-' . uniqid(),
                    'error' => null,
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => 'BDBulkSMS Response: ' . $body,
            ];
        } catch (Exception $e) {
            Log::error('BDBulkSMS Driver Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check account balance from BDBulkSMS / Greenweb API.
     */
    public function getBalance(SmsGateway $gateway): array
    {
        try {
            $token = $gateway->api_key ?: config('services.bdbulksms.token', env('BDBULKSMS_TOKEN', env('GREENWEB_SMS_TOKEN')));

            if (empty($token)) {
                return ['success' => false, 'balance' => null, 'error' => 'BDBulkSMS API Token is missing.'];
            }

            // g_api endpoint with token & balance action
            $response = Http::timeout(10)->get($this->balanceApiUrl, [
                'token' => $token,
                'balance' => 'true',
                'json' => 'true',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    $balance = $data['balance'] ?? $data['sms_balance'] ?? $data['credits'] ?? null;
                    if ($balance !== null) {
                        return [
                            'success' => true,
                            'balance' => (string)$balance,
                            'error' => null,
                        ];
                    }
                }

                // If returned plain string (number of SMS or balance amount)
                $body = trim($response->body());
                if (is_numeric($body)) {
                    return [
                        'success' => true,
                        'balance' => $body,
                        'error' => null,
                    ];
                }

                return [
                    'success' => true,
                    'balance' => $body,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'balance' => null,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'balance' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
