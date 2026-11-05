<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\MpesaTransaction;
use App\Services\Billing\PaystackVerifier;
use App\Services\MpesaService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pay per event (Admin -> Settings -> Pay per event): the organiser asks
 * for a quote, TenaFi prices it (Admin -> Event quotes), and the organiser
 * pays that server-side amount by M-Pesa or card. Paying switches on the
 * event's WiFi page. Account owners only; team members don't pay.
 */
class EventPaymentController extends Controller
{
    public function requestQuote(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($event->needsPayment(), 404);

        $data = $request->validate([
            'expected_attendees' => 'required|integer|min:1|max:100000',
            'request_note' => 'nullable|string|max:300',
        ], ['expected_attendees.required' => 'Roughly how many people are you expecting?']);

        $event->forceFill($data + ['quote_status' => 'requested', 'quote_requested_at' => now(), 'quote_amount' => null, 'quoted_at' => null])->save();
        NotificationService::eventQuoteRequested($event);

        return back()->with('success', 'Quote requested. We’ll send you the price shortly.');
    }

    public function payMpesa(Request $request, Event $event, MpesaService $mpesa): RedirectResponse
    {
        $this->authorize('update', $event);
        $this->payable($event);

        $data = $request->validate([
            'phone_number' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! preg_match('/^\+2547\d{8}$|^\+2541\d{8}$/', (string) Phone::toE164($value))) {
                    $fail('Enter a Safaricom number, e.g. 0712 345 678.');
                }
            }],
        ]);
        $phone = Phone::toE164($data['phone_number']);

        $response = $mpesa->initiateStkPush($phone, $event->quote_amount, 'TenaFi', 'Event');

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'Failed to start the M-Pesa payment.');
        }

        MpesaTransaction::create([
            'user_id' => $request->user()->id,
            'MerchantRequestID' => $response['data']['MerchantRequestID'],
            'CheckoutRequestID' => $response['data']['CheckoutRequestID'],
            'Amount' => $event->quote_amount,
            'PhoneNumber' => $phone,
            'Status' => 'pending',
            'ResultDesc' => $response['data']['ResponseDescription'] ?? 'Initiated',
            'meta' => static::meta($event),
        ]);

        return back()->with('success', 'M-Pesa request sent to your phone. Enter your PIN to pay KES '.number_format($event->quote_amount).'.');
    }

    public function payPaystack(Request $request, Event $event, PaystackVerifier $paystack, SubscriptionService $subscriptions): RedirectResponse
    {
        $this->authorize('update', $event);
        $this->payable($event);

        $data = $request->validate(['reference' => 'required|string|max:100']);

        if (MpesaTransaction::where('CheckoutRequestID', $data['reference'])->exists()
            || ! $paystack->verify($data['reference'], $event->quote_amount)) {
            return back()->with('error', 'Payment verification failed. Please try again.');
        }

        $transaction = MpesaTransaction::create([
            'user_id' => $request->user()->id,
            'MerchantRequestID' => 'paystack_'.time(),
            'CheckoutRequestID' => $data['reference'],
            'Amount' => $event->quote_amount,
            'PhoneNumber' => $request->user()->phone_number ?? 'N/A',
            'Status' => 'completed',
            'ResultDesc' => 'Paystack payment completed',
            'meta' => static::meta($event),
        ]);
        $event->markPaid();
        $subscriptions->sendReceipt($request->user(), $transaction);

        return back()->with('success', "Paid. {$event->name}’s WiFi page is on.");
    }

    /** What a payment was for: read back by the M-Pesa callback and the payment history. */
    public static function meta(Event $event): array
    {
        return ['type' => 'event', 'event_id' => $event->id, 'event_name' => $event->name, 'total' => $event->quote_amount];
    }

    /** Only a priced, unpaid event can be paid, by the account owner. */
    private function payable(Event $event): void
    {
        abort_if(request()->user()->isTeamMember(), 403);
        abort_unless($event->quote_status === 'quoted' && $event->quote_amount > 0 && ! $event->paid_at, 404);
    }
}
