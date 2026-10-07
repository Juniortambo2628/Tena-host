<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Services\Messaging\Messenger;
use App\Support\Brand;
use App\Support\Phone;
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
        $response = $this->mpesa->initiateStkPush(
            $phone,
            $order->total,
            'TF'.$order->id,
            'TenaFi extra',
            route('mpesa.extras.callback'),
        );

        if (! $response['success']) {
            $order->update(['payment_status' => 'failed', 'payer_phone' => $phone]);

            return ['status' => 'failed', 'message' => "We couldn't start the M-Pesa payment. Please try again."];
        }

        $order->update([
            'payment_status' => 'pending',
            'payer_phone' => $phone,
            'mpesa_checkout_request_id' => $response['data']['CheckoutRequestID'] ?? null,
        ]);

        return ['status' => 'pending', 'message' => 'Check your phone and enter your M-Pesa PIN to pay KES '.number_format((float) $order->total).'.'];
    }

    /**
     * Apply Safaricom's STK callback. Repeated callbacks are ignored.
     *
     * @param  array<string, mixed>  $callback  Body.stkCallback
     */
    public function handleCallback(array $callback): ?Order
    {
        $order = Order::with(['guest', 'amenity', 'property.host'])
            ->where('mpesa_checkout_request_id', $callback['CheckoutRequestID'] ?? '')
            ->first();

        if (! $order || $order->payment_status !== 'pending') {
            return $order;
        }

        if ((int) ($callback['ResultCode'] ?? 1) !== 0) {
            $order->update(['payment_status' => 'failed']);

            return $order;
        }

        $items = collect($callback['CallbackMetadata']['Item'] ?? [])->pluck('Value', 'Name');
        $paid = (float) ($items['Amount'] ?? 0);

        if ($paid < (float) $order->total) {
            Log::warning('M-Pesa extras payment below order total', ['order' => $order->id, 'paid' => $paid]);
            $order->update(['payment_status' => 'failed', 'mpesa_receipt' => $items['MpesaReceiptNumber'] ?? null]);

            return $order;
        }

        $order->update([
            'payment_status' => 'paid',
            'mpesa_receipt' => $items['MpesaReceiptNumber'] ?? null,
            'paid_at' => now(),
            'platform_fee' => round((float) $order->total * self::feePercent() / 100, 2),
        ]);

        $this->notifyHost($order);

        return $order;
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
