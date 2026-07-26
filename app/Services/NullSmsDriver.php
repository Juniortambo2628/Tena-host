<?php

namespace App\Services;

class NullSmsDriver implements SmsDriverInterface
{
    public function send(string $to, string $message): array
    {
        \Illuminate\Support\Facades\Log::info('SMS dispatch skipped (null driver)', [
            'to' => $to,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'message' => 'SMS dispatch skipped: no SMS driver configured.',
        ];
    }
}
