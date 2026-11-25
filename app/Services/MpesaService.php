<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Phone;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lipa na M-Pesa Online (STK push) on TenaFi's paybill or till. Used for
 * host subscriptions and guest extras. Credentials are managed in
 * Admin → M-Pesa (falling back to .env); until they're filled in, the
 * shortcode and passkey hold Safaricom's public sandbox placeholders and
 * isConfigured() is false.
 */
class MpesaService
{
    /** Safaricom's public sandbox paybill and passkey (Daraja docs). */
    public const SANDBOX_SHORTCODE = '174379';

    public const SANDBOX_PASSKEY = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

    public const PAYBILL = 'CustomerPayBillOnline';

    public const TILL = 'CustomerBuyGoodsOnline';

    /**
     * Prompts sent before callback tokens existed have no token. Their
     * callbacks are still taken on the old URL for this many minutes after the
     * prompt (an STK prompt expires long before then); after that the old URL
     * applies nothing.
     */
    public const LEGACY_CALLBACK_MINUTES = 10;

    protected string $baseUrl;

    /**
     * @param  array{key?: ?string, secret?: ?string, passkey?: ?string, shortcode?: ?string, env?: ?string, account_type?: ?string, callback_url?: ?string}  $config
     */
    public function __construct(protected array $config = [])
    {
        $this->config = $config ?: static::settings();
        $this->baseUrl = ($this->config['env'] ?? 'sandbox') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public static function fromSettings(): self
    {
        return new self(static::settings());
    }

    /**
     * Admin → M-Pesa values, falling back to .env, then sandbox placeholders.
     *
     * @return array<string, ?string>
     */
    public static function settings(): array
    {
        $secret = fn (string $key, ?string $fallback) => filled($stored = Setting::getValue($key))
            ? rescue(fn () => Crypt::decryptString($stored), null, false)
            : $fallback;

        return [
            'env' => Setting::getValue('mpesa_env') ?: config('services.mpesa.env', 'sandbox'),
            'account_type' => Setting::getValue('mpesa_account_type') ?: 'paybill',
            'shortcode' => Setting::getValue('mpesa_shortcode') ?: (config('services.mpesa.shortcode') ?: self::SANDBOX_SHORTCODE),
            'key' => Setting::getValue('mpesa_consumer_key') ?: config('services.mpesa.key'),
            'secret' => $secret('mpesa_consumer_secret', config('services.mpesa.secret')),
            'passkey' => $secret('mpesa_passkey', config('services.mpesa.passkey') ?: self::SANDBOX_PASSKEY),
            'callback_url' => config('services.mpesa.callback_url'),
        ];
    }

    /**
     * True once real API credentials are set (the placeholders alone aren't enough).
     */
    public function isConfigured(): bool
    {
        return filled($this->config['key'] ?? null) && filled($this->config['secret'] ?? null)
            && filled($this->config['passkey'] ?? null) && filled($this->config['shortcode'] ?? null);
    }

    public function getAccessToken(): ?string
    {
        $response = Http::withBasicAuth((string) $this->config['key'], (string) $this->config['secret'])
            ->get($this->baseUrl.'/oauth/v1/generate?grant_type=client_credentials');

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('M-Pesa Auth Failed: '.$response->body());

        return null;
    }

    /**
     * A fresh random token for one STK prompt's CallBackURL.
     */
    public static function newCallbackToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * The CallBackURL for one prompt: the subscription or extras callback with
     * the prompt's token on the end. MPESA_CALLBACK_URL, when set, replaces the
     * subscription callback's base (e.g. a tunnel in development).
     *
     * @param  'mpesa.callback'|'mpesa.extras.callback'  $route
     */
    public static function callbackUrl(string $route, string $token): string
    {
        $base = $route === 'mpesa.callback' ? (config('services.mpesa.callback_url') ?: route($route)) : route($route);

        return rtrim((string) $base, '/').'/'.$token;
    }

    /**
     * True when a token from a callback URL is the one stored for the prompt.
     */
    public static function tokenMatches(?string $stored, ?string $given): bool
    {
        return filled($stored) && filled($given) && hash_equals((string) $stored, (string) $given);
    }

    /**
     * The CallbackMetadata items of an stkCallback, by name (Amount, MpesaReceiptNumber, ...).
     *
     * @param  array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    public static function callbackItems(array $callback): array
    {
        $items = [];
        foreach ((array) ($callback['CallbackMetadata']['Item'] ?? []) as $item) {
            if (is_array($item) && isset($item['Name'])) {
                $items[(string) $item['Name']] = $item['Value'] ?? null;
            }
        }

        return $items;
    }

    /**
     * The payer can't change the amount on an STK prompt, so a paid amount must
     * equal what was prompted (the push rounds up to whole shillings).
     */
    public static function amountMatches(mixed $paid, float|int|string $prompted): bool
    {
        return is_numeric($paid) && abs((float) $paid - ceil((float) $prompted)) < 0.005;
    }

    /**
     * Send the "enter your M-Pesa PIN" prompt to a phone.
     *
     * @param  string  $callbackUrl  where Safaricom posts the result; build it with callbackUrl()
     * @param  string  $reference  shown to the payer as the account (max 12 chars)
     * @return array{success: bool, data?: array, message?: string}
     */
    public function initiateStkPush($phoneNumber, $amount, string $callbackUrl, $reference = 'TenaFi', string $description = 'TenaFi'): array
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return ['success' => false, 'message' => 'Failed to authenticate with M-Pesa'];
        }

        $shortcode = (string) $this->config['shortcode'];
        $timestamp = date('YmdHis');
        $phone = ltrim((string) Phone::toE164((string) $phoneNumber), '+');

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => base64_encode($shortcode.$this->config['passkey'].$timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => ($this->config['account_type'] ?? 'paybill') === 'till' ? self::TILL : self::PAYBILL,
            'Amount' => (int) ceil((float) $amount),
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => mb_substr($reference, 0, 12),
            'TransactionDesc' => mb_substr($description, 0, 13),
        ];

        $response = Http::withToken($token)->post($this->baseUrl.'/mpesa/stkpush/v1/processrequest', $payload);

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        Log::error('M-Pesa STK Push Failed: '.$response->body());

        return [
            'success' => false,
            'message' => 'STK Push failed: '.($response->json('errorMessage') ?? 'Unknown Error'),
            'raw' => $response->json(),
        ];
    }
}
