<?php

namespace App\Services\SmsDrivers;

use App\Contracts\SmsGatewayInterface;
use App\Models\SmsGateway;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use sms_net_bd\SMS;

class AlphaSmsDriver implements SmsGatewayInterface
{
    protected string $defaultApiUrl = 'https://api.sms.net.bd/sendsms';

    /**
     * Send an SMS via Alpha SMS (sms.net.bd).
     */
    public function send(string $recipient, string $message, SmsGateway $gateway): array
    {
        try {
            $apiKey = $gateway->api_key ?: config('services.sms_net_bd.api_key', env('SMS_NET_BD_API_KEY'));

            if (empty($apiKey)) {
                throw new Exception('Alpha SMS API key is not configured. Please set your API key in SMS Gateway settings or in .env as SMS_NET_BD_API_KEY.');
            }

            // Normalize phone number
            $phone = preg_replace('/[^0-9]/', '', $recipient);
            if (strlen($phone) === 10 && str_starts_with($phone, '1')) {
                $phone = '0' . $phone;
            }

            $senderId = $gateway->sender_id ?: config('services.sms_net_bd.sender_id', env('SMS_NET_BD_SENDER_ID'));

            // Sync environment variable for sms_net_bd\SMS package
            putenv("SMS_NET_BD_API_KEY={$apiKey}");
            $_ENV['SMS_NET_BD_API_KEY'] = $apiKey;
            $_SERVER['SMS_NET_BD_API_KEY'] = $apiKey;

            if (class_exists(SMS::class)) {
                $smsPackage = new SMS();
                try {
                    $response = $smsPackage->sendSMS($message, $phone, $senderId ?: null);

                    $requestId = is_array($response) ? ($response['request_id'] ?? ('ALPHASMS-' . uniqid())) : ('ALPHASMS-' . uniqid());

                    return [
                        'success' => true,
                        'message_id' => (string)$requestId,
                        'error' => null,
                        'data' => $response,
                    ];
                } catch (Exception $pkgEx) {
                    return [
                        'success' => false,
                        'message_id' => null,
                        'error' => $pkgEx->getMessage(),
                    ];
                }
            }

            // Direct HTTP fallback
            $apiUrl = $gateway->api_url ?: $this->defaultApiUrl;
            $params = [
                'api_key' => $apiKey,
                'msg' => $message,
                'to' => $phone,
            ];
            if (!empty($senderId)) {
                $params['sender_id'] = $senderId;
            }

            $response = Http::asForm()->timeout(15)->post($apiUrl, $params);

            if ($response->failed()) {
                throw new Exception("HTTP request failed with status {$response->status()}: {$response->body()}");
            }

            $data = $response->json();

            if (isset($data['error']) && (int)$data['error'] === 0) {
                $requestId = $data['data']['request_id'] ?? ('ALPHASMS-' . uniqid());
                return [
                    'success' => true,
                    'message_id' => (string)$requestId,
                    'error' => null,
                    'data' => $data['data'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => $data['msg'] ?? ('Alpha SMS error: ' . ($data['error'] ?? 'Unknown')),
            ];

        } catch (Exception $e) {
            Log::error("Alpha SMS Gateway Error: " . $e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send Scheduled SMS via Alpha SMS.
     */
    public function sendScheduled(string $recipient, string $message, string $schedule, SmsGateway $gateway): array
    {
        try {
            $apiKey = $gateway->api_key ?: config('services.sms_net_bd.api_key', env('SMS_NET_BD_API_KEY'));

            if (empty($apiKey)) {
                throw new Exception('Alpha SMS API key is missing.');
            }

            $phone = preg_replace('/[^0-9]/', '', $recipient);
            if (strlen($phone) === 10 && str_starts_with($phone, '1')) {
                $phone = '0' . $phone;
            }

            $senderId = $gateway->sender_id ?: config('services.sms_net_bd.sender_id', env('SMS_NET_BD_SENDER_ID'));

            putenv("SMS_NET_BD_API_KEY={$apiKey}");
            $_ENV['SMS_NET_BD_API_KEY'] = $apiKey;
            $_SERVER['SMS_NET_BD_API_KEY'] = $apiKey;

            if (class_exists(SMS::class)) {
                $smsPackage = new SMS();
                try {
                    $response = $smsPackage->sendScheduledSMS($message, $phone, $schedule, $senderId ?: null);
                    $requestId = is_array($response) ? ($response['request_id'] ?? ('ALPHASMS-' . uniqid())) : ('ALPHASMS-' . uniqid());

                    return [
                        'success' => true,
                        'message_id' => (string)$requestId,
                        'error' => null,
                    ];
                } catch (Exception $pkgEx) {
                    return [
                        'success' => false,
                        'message_id' => null,
                        'error' => $pkgEx->getMessage(),
                    ];
                }
            }

            $apiUrl = $gateway->api_url ?: $this->defaultApiUrl;
            $params = [
                'api_key' => $apiKey,
                'msg' => $message,
                'to' => $phone,
                'schedule' => $schedule,
            ];
            if (!empty($senderId)) {
                $params['sender_id'] = $senderId;
            }

            $response = Http::asForm()->timeout(15)->post($apiUrl, $params);
            $data = $response->json();

            if (isset($data['error']) && (int)$data['error'] === 0) {
                $requestId = $data['data']['request_id'] ?? ('ALPHASMS-' . uniqid());
                return [
                    'success' => true,
                    'message_id' => (string)$requestId,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'message_id' => null,
                'error' => $data['msg'] ?? 'Scheduling failed',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check account balance from Alpha SMS (sms.net.bd) API.
     */
    public function getBalance(SmsGateway $gateway): array
    {
        try {
            $apiKey = $gateway->api_key ?: config('services.sms_net_bd.api_key', env('SMS_NET_BD_API_KEY'));

            if (empty($apiKey)) {
                return ['success' => false, 'balance' => null, 'error' => 'API Key is missing.'];
            }

            putenv("SMS_NET_BD_API_KEY={$apiKey}");
            $_ENV['SMS_NET_BD_API_KEY'] = $apiKey;
            $_SERVER['SMS_NET_BD_API_KEY'] = $apiKey;

            if (class_exists(SMS::class)) {
                $smsPackage = new SMS();
                try {
                    $response = $smsPackage->getBalance();
                    $balance = is_array($response) ? ($response['balance'] ?? '0.00') : (string)$response;
                    return [
                        'success' => true,
                        'balance' => $balance,
                        'error' => null,
                    ];
                } catch (Exception $pkgEx) {
                    return [
                        'success' => false,
                        'balance' => null,
                        'error' => $pkgEx->getMessage(),
                    ];
                }
            }

            $response = Http::timeout(10)->get('https://api.sms.net.bd/user/balance/', [
                'api_key' => $apiKey,
            ]);

            $data = $response->json();

            if (isset($data['error']) && (int)$data['error'] === 0) {
                return [
                    'success' => true,
                    'balance' => $data['data']['balance'] ?? '0.00',
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'balance' => null,
                'error' => $data['msg'] ?? 'Unable to fetch balance.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'balance' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get SMS delivery report by Request ID.
     */
    public function getReport(string $requestId, SmsGateway $gateway): array
    {
        try {
            $apiKey = $gateway->api_key ?: config('services.sms_net_bd.api_key', env('SMS_NET_BD_API_KEY'));

            if (empty($apiKey)) {
                return ['success' => false, 'report' => null, 'error' => 'API Key is missing.'];
            }

            putenv("SMS_NET_BD_API_KEY={$apiKey}");
            $_ENV['SMS_NET_BD_API_KEY'] = $apiKey;
            $_SERVER['SMS_NET_BD_API_KEY'] = $apiKey;

            if (class_exists(SMS::class)) {
                $smsPackage = new SMS();
                try {
                    $report = $smsPackage->getReport($requestId);
                    return [
                        'success' => true,
                        'report' => $report,
                        'error' => null,
                    ];
                } catch (Exception $pkgEx) {
                    return [
                        'success' => false,
                        'report' => null,
                        'error' => $pkgEx->getMessage(),
                    ];
                }
            }

            $response = Http::timeout(10)->get("https://api.sms.net.bd/report/request/{$requestId}/", [
                'api_key' => $apiKey,
            ]);

            $data = $response->json();

            if (isset($data['error']) && (int)$data['error'] === 0) {
                return [
                    'success' => true,
                    'report' => $data['data'] ?? $data,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'report' => null,
                'error' => $data['msg'] ?? 'Unable to fetch report.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'report' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
