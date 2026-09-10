<?php

namespace App\Services;

use App\Contracts\SmsGatewayInterface;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\SmsGateway;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Services\SmsDrivers\GenericHttpSmsDriver;
use App\Services\SmsDrivers\LogSmsDriver;
use Exception;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Resolve Gateway Driver Instance.
     */
    public function resolveDriver(?string $driverName = null): SmsGatewayInterface
    {
        return match ($driverName) {
            'generic_http', 'greenweb', 'bulksmsbd' => new GenericHttpSmsDriver(),
            default => new LogSmsDriver(),
        };
    }

    /**
     * Parse and interpolate template variables ({name}, {amount}, {expiry_date}, etc.).
     */
    public function parseTemplate(string $templateText, array $variables): string
    {
        $parsed = $templateText;
        foreach ($variables as $key => $value) {
            $parsed = str_replace('{' . $key . '}', (string) $value, $parsed);
        }

        return $parsed;
    }

    /**
     * Send an SMS message using the active gateway or specified gateway.
     */
    public function sendSms(
        string $recipient,
        string $message,
        ?int $userId = null,
        ?int $customerId = null,
        ?int $templateId = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): SmsLog {
        $activeGateway = SmsGateway::where('is_active', true)->first();

        // Fallback default log gateway if none configured
        if (!$activeGateway) {
            $activeGateway = SmsGateway::firstOrCreate(
                ['driver' => 'log'],
                ['name' => 'Local System Log Gateway', 'is_active' => true]
            );
        }

        $driver = $this->resolveDriver($activeGateway->driver);
        $result = $driver->send($recipient, $message, $activeGateway);

        $smsLog = SmsLog::create([
            'recipient' => $recipient,
            'customer_id' => $customerId,
            'template_id' => $templateId,
            'gateway_id' => $activeGateway->id,
            'message' => $message,
            'status' => $result['success'] ? 'sent' : 'failed',
            'provider_message_id' => $result['message_id'] ?? null,
            'error_message' => $result['error'] ?? null,
            'sent_at' => $result['success'] ? now() : null,
            'sent_by' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        AuditLog::log('sms_sent', 'sms', $smsLog, null, [
            'recipient' => $recipient,
            'status' => $smsLog->status,
            'gateway' => $activeGateway->name,
        ]);

        return $smsLog;
    }

    /**
     * Send SMS by Template Code with automated Customer variable interpolation.
     */
    public function sendByTemplate(
        string $templateCode,
        Customer $customer,
        array $extraVariables = [],
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null
    ): ?SmsLog {
        $template = SmsTemplate::where('code', $templateCode)->first();
        if (!$template || !$template->is_auto_enabled) {
            return null;
        }

        $phone = $customer->primaryContact?->phone ?? $customer->primaryContact?->phone_number;
        if (!$phone) {
            return null;
        }

        // Master Plan Rule 30: Prevent duplicate automatic SMS for the same event
        if ($entityType && $entityId) {
            $alreadySent = SmsLog::where('template_id', $template->id)
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->where('status', 'sent')
                ->exists();

            if ($alreadySent) {
                return null;
            }
        }

        // Default standard variables
        $activeConnection = $customer->connections()->latest('id')->first();
        $variables = array_merge([
            'name' => $customer->name,
            'customer_code' => $customer->customer_code,
            'package' => $activeConnection?->package?->name ?? 'Standard Package',
            'amount' => '0.00',
            'expiry_date' => $activeConnection?->expiry_date?->toDateString() ?? 'N/A',
            'due' => number_format((float) max(0, -$customer->balance), 2),
        ], $extraVariables);

        $message = $this->parseTemplate($template->template, $variables);

        return $this->sendSms(
            $phone,
            $message,
            $userId,
            $customer->id,
            $template->id,
            $entityType,
            $entityId
        );
    }

    /**
     * Retry sending a failed SMS log.
     */
    public function retrySms(SmsLog $log, int $userId): SmsLog
    {
        $activeGateway = SmsGateway::where('is_active', true)->first() ?? $log->gateway;
        $driver = $this->resolveDriver($activeGateway?->driver);

        $result = $driver->send($log->recipient, $log->message, $activeGateway);

        $log->update([
            'status' => $result['success'] ? 'sent' : 'failed',
            'provider_message_id' => $result['message_id'] ?? $log->provider_message_id,
            'error_message' => $result['error'],
            'sent_at' => $result['success'] ? now() : null,
            'gateway_id' => $activeGateway?->id,
        ]);

        AuditLog::log('sms_retried', 'sms', $log, null, [
            'recipient' => $log->recipient,
            'status' => $log->status,
        ]);

        return $log;
    }
}
