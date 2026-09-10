<?php

namespace App\Contracts;

use App\Models\SmsGateway;

interface SmsGatewayInterface
{
    /**
     * Send an SMS message to a single recipient.
     *
     * @param string $recipient Phone number e.g. 017XXXXXXXX or +88017XXXXXXXX
     * @param string $message Text content of the SMS
     * @param SmsGateway $gateway Configured gateway credentials
     * @return array ['success' => bool, 'message_id' => ?string, 'error' => ?string]
     */
    public function send(string $recipient, string $message, SmsGateway $gateway): array;
}
