<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Services\Messaging\Messenger;
use App\Support\Brand;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Guest extras paid by M-Pesa on TenaFi's paybill (Admin → M-Pesa).
 *
 * A priced order sends the guest an STK prompt; Safaricom's callback marks
 * it paid, TenaFi's fee is recorded, and the host is told. Until M-Pesa is
 * set up (or extras payments are switched off), orders still go through and
 * are paid at the property.
 */
class ExtrasPaymentService
{
    public function __construct(
        protected MpesaService $mpesa,
        protected Messenger $messenger,
    ) {}

    public function enabled(): bool
    {
        return (bool) Setting::getValue('mpesa_extras_enabled', true) && $this->mpesa->isConfigured();
    }

    public static function feePercent(): float
    {
        return (float) Setting::getValue('mpesa_extras_fee_percent', 0);
    }

    /**
     * Start payment for a new order.
     *
     * @return array{status: string, message: string}
     */
    public function checkout(Order $order, ?string $phone): array
    {
        if ((float) $order->total <= 0) {
            $order->update(['payment_status' => 'not_required']);

            return ['status' => 'not_required', 'message' => 'Order placed.'];
        }

        if (! $this->enabled()) {
            $order->update(['payment_status' => 'unpaid']);

            return ['status' => 'unpaid', 'message' => 'Order placed. Please pay your host directly.'];
        }

        $phone = Phone::toE164($phone);
        $token = MpesaService::newCallbackToken();
        $response = $this->mpesa->initiateStkPush(
            $phone,
            $order->total,
            MpesaService::callbackUrl('mpesa.extras.callback', $token),
            'TF'.$order->id,
            'TenaFi extra',
        );

        if (! $response['success']) {
            $order->update(['payment_status' => 'failed', 'payer_phone' => $phone]);

            return ['status' => 'failed', 'message' => "We couldn't start the M-Pesa payment. Please try again."];
        }

        $order->update([
            'payment_status' => 'pending',
            'payer_phone' => $phone,
            'mpesa_checkout_request_id' => $response['data']['CheckoutRequestID'] ?? null,
            'mpesa_callback_token' => $token,
        ]);

        return ['status' => 'pending', 'message' => 'Check your phone and enter your M-Pesa PIN to pay KES '.number_format((float) $order->total).'.'];
    }

    /**
     * Apply Safaricom's STK callback. It is applied only when the URL's token
     * and the CheckoutRequestID both match a pending order, and only once.
     * The paid amount must equal the prompt; anything else marks the order
     * failed. Returns the order it applied to, or null.
     *
     * @param  array<string, mixed>  $callback  Body.stkCallback
     */
    public function handleCallback(array $callback, ?string $token = null): ?Order
    {
        $resultCode = isset($callback['ResultCode']) ? (int) $callback['ResultCode'] : null;
        $order = $this->findOrder($callback, $token);

        if (! $order) {
            Log::warning('M-Pesa extras callback ignored', ['result_code' => $resultCode]);

            return null;
        }

        $outcome = DB::transaction(function () use ($order, $callback, $resultCode) {
            // A repeated callback waits on the row lock, then finds the order settled.
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->first();
            if (! $locked || $locked->payment_status !== 'pending') {
                return 'duplicate';
            }

            if ($resultCode !== 0) {
                $locked->update(['payment_status' => 'failed']);

                return 'failed';
            }

            $items = MpesaService::callbackItems($callback);
            $receipt = isset($items['MpesaReceiptNumber']) ? (string) $items['MpesaReceiptNumber'] : null;

            if (! MpesaService::amountMatches($items['Amount'] ?? null, $locked->total)) {
                // Keep the receipt so the payment can be found and refunded.
                $locked->update(['payment_status' => 'failed', 'mpesa_receipt' => $receipt]);

                return 'amount_mismatch';
            }

            $locked->update([
                'payment_status' => 'paid',
                'mpesa_receipt' => $receipt,
                'paid_at' => now(),
                'platform_fee' => round((float) $locked->total * self::feePercent() / 100, 2),
            ]);

            return 'paid';
        });

        Log::info('M-Pesa extras callback', ['result_code' => $resultCode, 'order' => $order->id, 'outcome' => $outcome]);

        $order->refresh();
        if ($outcome === 'paid') {
            $this->notifyHost($order);
        }

        return $order;
    }

    /**
     * The pending order this callback answers, or null.
     *
     * @param  array<string, mixed>  $callback
     */
    protected function findOrder(array $callback, ?string $token): ?Order
    {
        $checkoutId = (string) ($callback['CheckoutRequestID'] ?? '');
        if ($checkoutId === '') {
            return null;
        }

        $query = Order::with(['guest', 'amenity', 'property.host']);

        if ($token !== null) {
            $order = $query->where('mpesa_callback_token', $token)->first();

            return $order
                && MpesaService::tokenMatches($order->mpesa_callback_token, $token)
                && hash_equals((string) $order->mpesa_checkout_request_id, $checkoutId)
                ? $order : null;
        }

        // The old token-less URL: only prompts sent before tokens existed, for a short while.
        return $query->whereNull('mpesa_callback_token')
            ->where('payment_status', 'pending')
            ->where('mpesa_checkout_request_id', $checkoutId)
            ->where('updated_at', '>=', now()->subMinutes(MpesaService::LEGACY_CALLBACK_MINUTES))
            ->first();
    }

    protected function notifyHost(Order $order): void
    {
        NotificationService::orderPlaced($order->id);

        $host = $order->property?->host;
        if ($host?->phone_number) {
            $this->messenger->send('whatsapp', $host->phone_number, sprintf(
                '%s: %s paid KES %s for %s at %s (M-Pesa %s).',
                Brand::name(),
                $order->guest?->first_name ?? 'A guest',
                number_format((float) $order->total),
                $order->amenity?->name ?? 'an extra',
                $order->property->name,
                $order->mpesa_receipt,
            ));
        }
    }
}
