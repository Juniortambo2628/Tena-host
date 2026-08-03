<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /**
     * Generate and send an OTP to the given identifier (email or phone).
     */
    public function send(string $identifier, string $purpose = 'guest_login', array $metadata = []): Otp
    {
        // Invalidate previous OTPs for this identifier/purpose.
        Otp::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = Otp::create([
            'identifier' => $identifier,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(15),
            'metadata' => $metadata,
        ]);

        $this->deliver($otp);

        return $otp;
    }

    /**
     * Verify an OTP code.
     */
    public function verify(string $identifier, string $code, string $purpose = 'guest_login'): ?Otp
    {
        $otp = Otp::valid($identifier, $purpose)
            ->where('code', $code)
            ->first();

        if ($otp) {
            $otp->markUsed();
        }

        return $otp;
    }

    /**
     * Deliver the OTP via email or SMS.
     */
    protected function deliver(Otp $otp): void
    {
        if (filter_var($otp->identifier, FILTER_VALIDATE_EMAIL)) {
            Mail::to($otp->identifier)->send(new \App\Mail\OtpMail(code: $otp->code));

            return;
        }

        // For phone numbers, use configured SMS driver.
        $driverClass = config('services.sms.driver', NullSmsDriver::class);
        $driver = app($driverClass);
        $driver->send($otp->identifier, "Your TENA verification code is: {$otp->code}");
    }
}
