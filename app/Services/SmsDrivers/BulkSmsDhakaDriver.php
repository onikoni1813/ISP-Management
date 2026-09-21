<?php

namespace App\Services\SmsDrivers;

use App\Contracts\SmsGatewayInterface;
use App\Models\SmsGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkSmsDhakaDriver implements SmsGatewayInterface
{
    /**
     * Official working API URL for Bulk SMS Dhaka.
     * Note: bulksmsdhaka.net is the active API host (bulksmsdhaka.com returns 404 HTML).
     */
    protected string $defaultApiUrl = 'https://bulksmsdhaka.net/api';

    /**
     * Send an SMS via Bulk SMS Dhaka API.
     */
    public function send(string $recipient, string $message, SmsGateway $gateway): array
    {
        try {
            $apiKey = $gateway->api_key ?: env('BULKSMSDHAKA_API_KEY');

            if (empty($apiKey)) {
                throw new Exception('Bulk SMS Dhaka API Key is not configured. Please set your API Key in SMS Gateway settings or in .env as BULKSMSDHAKA_API_KEY.');
            }

            // Normalize phone number (11 digits e.g. 017XXXXXXXX)
            $phone = preg_replace('/[^0-9]/', '', $recipient);
            if (strlen($phone) === 13 && str_starts_with($phone, '880')) {
                $phone = substr($phone, 2);
            } elseif (strlen($phone) === 10 && str_starts_with($phone, '1')) {
                $phone = '0' . $phone;
            }

            $callerId = $gateway->sender_id ?: '1234';
            
            // Resolve base API URL (fix legacy .com to .net if needed)
            $baseUrl = $this->resolveBaseUrl($gateway->api_url);

            // Bulk SMS Dhaka API endpoint
            $url = "{$baseUrl}/sendtext";

            // Supports GET and POST with apikey, callerID, number, message
            $response = Http::timeout(15)
                ->acceptJson()
                ->get($url, [
                    'apikey' => $apiKey,
                    'callerID' => $callerId,
                    'number' => $phone,
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                // Try POST fallback
                $response = Http::asForm()->timeout(15)
                    ->acceptJson()
                    ->post($url, [
                        'apikey' => $apiKey,
                        'callerID' => $callerId,
                        'number' => $phone,
                        'message' => $message,
                    ]);
            }

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message_id' => null,
                    'error' => 'HTTP ' . $response->status() . ': ' . $this->cleanErrorMessage($response->body()),
                ];
            }

            $data = $response->json();
            if (is_array($data)) {
                $status = (string)($data['Status'] ?? $data['status'] ?? '');
                $success = ($data['Success'] ?? $data['success'] ?? '');
                $msg = (string)($data['Message'] ?? $data['message'] ?? '');

                $isSuccess = $success === true 
                    || $success === 'true' 
                    || $status === '1000' 
                    || $status === '100' 
                    || stripos($msg, 'success') !== false;

                if ($isSuccess) {
                    return [
                        'success' => true,
                        'message_id' => $data['message_id'] ?? $data['Message_ID'] ?? ('BSMD-' . uniqid()),
                        'error' => null,
                        'data' => $data,
                    ];
                }

                return [
                    'success' => false,
                    'message_id' => null,
                    'error' => $msg ?: 'Bulk SMS Dhaka dispatch failed',
                    'data' => $data,
                ];
            }

            $body = $response->body();
            if (stripos($body, 'success') !== false || stripos($body, '1000') !== false) {
                return [
                    'success' => true,
                    'message_id' => 'BSMD-' . uniqid(),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Bulk SMS Dhaka response: ' . $this->cleanErrorMessage($body),
            ];

        } catch (Exception $e) {
            Log::error("BulkSMSDhaka Gateway Error: " . $e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check live account balance from Bulk SMS Dhaka.
     */
    public function getBalance(SmsGateway $gateway): array
    {
        try {
            $apiKey = $gateway->api_key ?: env('BULKSMSDHAKA_API_KEY');

            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'balance' => null,
                    'error' => 'Bulk SMS Dhaka API Key is missing. Please provide API Key in settings or .env.',
                ];
            }

            $baseUrl = $this->resolveBaseUrl($gateway->api_url);
            $url = "{$baseUrl}/getBalance";

            $response = Http::timeout(10)
                ->acceptJson()
                ->get($url, [
                    'apikey' => $apiKey,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'balance' => null,
                    'error' => 'HTTP ' . $response->status() . ': ' . $this->cleanErrorMessage($response->body()),
                ];
            }

            $data = $response->json();

            if (is_array($data)) {
                if (isset($data['Balance']) || isset($data['balance'])) {
                    return [
                        'success' => true,
                        'balance' => (string)($data['Balance'] ?? $data['balance']),
                        'error' => null,
                        'raw' => $data,
                    ];
                }

                return [
                    'success' => false,
                    'balance' => null,
                    'error' => $data['Message'] ?? $data['message'] ?? 'Unable to retrieve balance.',
                ];
            }

            if (is_numeric(trim($response->body()))) {
                return [
                    'success' => true,
                    'balance' => trim($response->body()),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'balance' => null,
                'error' => 'Unexpected response from Bulk SMS Dhaka API.',
            ];

        } catch (Exception $e) {
            Log::error("BulkSMSDhaka getBalance error: " . $e->getMessage());

            return [
                'success' => false,
                'balance' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolves the working API host, correcting bulksmsdhaka.com to bulksmsdhaka.net
     */
    protected function resolveBaseUrl(?string $url): string
    {
        $resolved = trim($url ?: $this->defaultApiUrl);
        $resolved = rtrim($resolved, '/');

        // bulksmsdhaka.com API endpoints were migrated to bulksmsdhaka.net
        if (str_contains($resolved, 'bulksmsdhaka.com')) {
            $resolved = str_replace('bulksmsdhaka.com', 'bulksmsdhaka.net', $resolved);
        }

        return $resolved;
    }

    /**
     * Strip HTML or truncate long error responses
     */
    protected function cleanErrorMessage(string $body): string
    {
        $plain = strip_tags($body);
        $plain = preg_replace('/\s+/', ' ', trim($plain));
        return substr($plain, 0, 150);
    }
}
