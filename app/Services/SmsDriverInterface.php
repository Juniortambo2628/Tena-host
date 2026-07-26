<?php

namespace App\Services;

interface SmsDriverInterface
{
    /**
     * Send an SMS message.
     *
     * @param  string  $to  E.164 phone number
     * @return array{success: bool, message?: string}
     */
    public function send(string $to, string $message): array;
}
