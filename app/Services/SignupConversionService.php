<?php

namespace App\Services;

use App\Mail\UserInvitationMail;
use App\Models\Registration;
use App\Models\User;
use App\Services\Messaging\Messenger;
use App\Support\Phone;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Turns a public sign-up into a host account: the user (email optional),
 * their first property and their chosen plan, then invites them by email
 * and/or WhatsApp with a link to set a password. Converting again re-sends
 * the invite, which is also how a phone-only user gets a fresh link.
 */
class SignupConversionService
{
    public const INVITE_DAYS = 7;

    public function __construct(protected Messenger $messenger) {}

    /**
     * @param  list<string>  $channels  'email' and/or 'whatsapp'
     * @return array{user: User, sent: list<string>}
     */
    public function convert(Registration $registration, array $channels = ['email', 'whatsapp']): array
    {
        $user = $registration->user_id ? User::find($registration->user_id) : null;
        $user ??= $this->findOrCreateUser($registration);

        if (! $user->properties()->exists()) {
            $user->properties()->create([
                'name' => $registration->business_name ?: "{$user->first_name}'s ".($registration->type === Registration::TYPE_BUSINESS ? 'business' : 'property'),
                'address' => $registration->location,
            ]);
        }

        $registration->update(['status' => 'converted', 'user_id' => $user->id]);

        return ['user' => $user, 'sent' => $this->invite($user, $channels)];
    }

    /**
     * @param  list<string>  $channels
     * @return list<string> the channels the invite went out on
     */
    public function invite(User $user, array $channels): array
    {
        $link = URL::temporarySignedRoute('invitation.show', now()->addDays(self::INVITE_DAYS), ['user' => $user->id]);
        $sent = [];

        if (in_array('email', $channels, true) && $user->email) {
            try {
                Mail::to($user->email)->send(new UserInvitationMail(
                    name: $user->first_name,
                    role: $user->role,
                    actionUrl: $link,
                    invitedBy: 'The TenaFi team',
                ));
                $sent[] = 'email';
            } catch (\Throwable $e) {
                Log::error('Invite email failed: '.$e->getMessage(), ['user' => $user->id]);
            }
        }

        if (in_array('whatsapp', $channels, true) && $user->phone_number) {
            $result = $this->messenger->send('whatsapp', $user->phone_number,
                "Hi {$user->first_name}, your TenaFi account is ready. Set your password to sign in (link valid for ".self::INVITE_DAYS." days): {$link}");

            if ($result['success']) {
                $sent[] = $result['channel'];
            }
        }

        return $sent;
    }

    protected function findOrCreateUser(Registration $registration): User
    {
        $email = $registration->email ? strtolower($registration->email) : null;
        $phone = Phone::toE164($registration->phone);

        $existing = User::query()
            ->where(fn ($q) => $q
                ->when($email, fn ($q) => $q->orWhere('email', $email))
                ->when($phone, fn ($q) => $q->orWhere('phone_number', $phone)))
            ->first();

        if ($existing) {
            return $existing;
        }

        $answers = $registration->answers ?? [];
        $plan = $answers['plan'] ?? null;

        return User::create([
            'username' => Str::slug($registration->first_name ?: 'host').'-'.Str::lower(Str::random(6)),
            'first_name' => $registration->first_name ?: 'Host',
            'last_name' => $registration->last_name ?? '',
            'email' => $email,
            'phone_number' => $phone,
            'role' => 'host',
            'password' => Hash::make(Str::random(40)),
            'billing_plan' => array_key_exists((string) $plan, config('billing.plans')) ? $plan : null,
            'billing_units' => $this->units($registration->units ?? $answers['units'] ?? $answers['locations'] ?? null),
        ]);
    }

    /**
     * "10-49" → 10, "50+" → 50, "2 to 5" → 2: the low end of the range picked.
     */
    protected function units(?string $answer): int
    {
        return preg_match('/\d+/', (string) $answer, $m) ? max(1, (int) $m[0]) : 1;
    }
}
