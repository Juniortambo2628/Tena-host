<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\PropertyTransfer;
use App\Services\PropertyTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Properties -> Transfers: hand a property to another TenaFi account, and
 * accept or decline one offered to you (docs/plans/property-transfer.md,
 * step 1). Account owners only; off until Admin -> Settings switches it on.
 */
class PropertyTransferController extends Controller
{
    public function __construct(protected PropertyTransferService $transfers) {}

    public function index(Request $request): Response
    {
        abort_unless(PropertyTransferService::enabled(), 404);
        PropertyTransfer::expireStale();
        $user = $request->user();

        $row = fn (PropertyTransfer $t) => [
            'id' => $t->id,
            'property' => $t->property?->name,
            'from' => trim("{$t->fromUser?->first_name} {$t->fromUser?->last_name}") ?: $t->fromUser?->email,
            'to_email' => $t->to_email,
            'status' => $t->status,
            'expires_at' => $t->expires_at->toIso8601String(),
            'decided_at' => $t->decided_at?->toIso8601String(),
            'created_at' => $t->created_at->toIso8601String(),
            'moved' => $t->moved,
            // Step 2: the guests told who runs it now, and when messages to them can start.
            'notice' => $t->notice_status ? ['status' => $t->notice_status, 'sent' => $t->notice_sent, 'total' => $t->notice_total] : null,
            'hold_until' => $t->hold_until?->toIso8601String(),
            // Step 3: accepted transfers cool off before the hand-over.
            'completes_at' => $t->completes_at?->toIso8601String(),
            'completed_at' => $t->completed_at?->toIso8601String(),
            'held' => (bool) $t->held_at,
            'cancelled_by' => $t->cancelled_by,
            // Step 4: device payments the new owner takes over.
            'reversed_at' => $t->reversed_at?->toIso8601String(),
            'devices' => $t->device_takeover ? ['count' => $t->device_count, 'balance' => $t->device_balance_kes, 'monthly' => $t->device_monthly_kes] : null,
        ];

        $incoming = PropertyTransfer::incomingFor($user)->with(['property', 'fromUser:id,first_name,last_name,email'])->latest('id')->limit(50)->get();

        return Inertia::render('Host/Transfers/Index', [
            // With what's still owed on their devices, to pay off or hand over.
            'properties' => $user->properties()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'devices' => PropertyTransferService::deviceDebt($p)]),
            'outgoing' => PropertyTransfer::where('from_user_id', $user->id)->with(['property', 'fromUser:id,first_name,last_name,email'])->latest('id')->limit(50)->get()->map($row),
            // Waiting offers show what would move, so the recipient knows what they're accepting.
            'incoming' => $incoming->map(fn (PropertyTransfer $t) => $row($t) + ($t->isPending() && $t->property ? ['preview' => [
                'guests' => $t->property->guests()->count(),
                'access_points' => $t->property->accessPoints()->count(),
                'campaigns' => Campaign::where('property_id', $t->property_id)->count(),
            ]] : [])),
            'validDays' => PropertyTransfer::VALID_DAYS,
            'holdDays' => PropertyTransfer::CAMPAIGN_HOLD_DAYS,
            'coolingOffHours' => PropertyTransfer::COOLING_OFF_HOURS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(PropertyTransferService::enabled(), 404);
        $user = $request->user();

        $data = $request->validate([
            'property_id' => ['required', Rule::exists('properties', 'id')->where('user_id', $user->id)],
            'email' => 'required|email|max:255',
            // Re-enter the password: handing over a property can't be undone by the owner.
            'password' => 'required|current_password',
            // A device still being paid off: the new owner takes over the payments.
            'device_takeover' => 'sometimes|boolean',
        ], [
            'property_id.exists' => 'Pick one of your own properties.',
            'password.current_password' => 'That password isn’t right.',
        ]);

        $property = $user->properties()->findOrFail($data['property_id']);
        $this->transfers->start($user, $property, $data['email'], $request->ip(), (bool) ($data['device_takeover'] ?? false));

        return back()->with('success', "Transfer offered to {$data['email']}. They have ".PropertyTransfer::VALID_DAYS.' days to accept; you can cancel until then.');
    }

    public function accept(Request $request, PropertyTransfer $transfer): RedirectResponse
    {
        abort_unless(PropertyTransferService::enabled(), 404);
        $transfer = $this->transfers->accept($transfer, $request->user(), $request->ip(), $request->boolean('takeover'));

        return back()->with('success', "Accepted. {$transfer->property->name} becomes yours after a ".PropertyTransfer::COOLING_OFF_HOURS.'-hour cooling-off'
            .($transfer->held_at ? ', once TenaFi has confirmed the device payments with you.' : '.'));
    }

    public function decline(Request $request, PropertyTransfer $transfer): RedirectResponse
    {
        abort_unless(PropertyTransferService::enabled(), 404);
        $this->transfers->decline($transfer, $request->user(), $request->ip());

        return back()->with('success', 'Transfer declined.');
    }

    /**
     * "It wasn't me" from the owner's email: a signed link, no sign-in. GET shows
     * a confirm button (email scanners open links); POST cancels.
     */
    public function cancelLink(Request $request, PropertyTransfer $transfer): HttpResponse
    {
        $transfer->loadMissing('property');
        $done = false;
        if ($request->isMethod('post') && $transfer->isOpen()) {
            $this->transfers->cancelFromLink($transfer, $request->ip());
            $done = true;
        }

        return response()->view('transfers.cancel', [
            'done' => $done,
            'open' => $transfer->fresh()->isOpen(),
            'propertyName' => $transfer->property?->name ?? 'Your property',
            'toEmail' => $transfer->to_email,
            'statusText' => ['completed' => 'completed', 'declined' => 'declined', 'cancelled' => 'cancelled', 'expired' => 'expired'][$transfer->fresh()->status] ?? 'closed',
            'action' => $request->fullUrl(),
        ]);
    }

    public function cancel(Request $request, PropertyTransfer $transfer): RedirectResponse
    {
        abort_unless(PropertyTransferService::enabled(), 404);
        $this->transfers->cancel($transfer, $request->user(), $request->ip());

        return back()->with('success', 'Transfer cancelled. The property stays yours.');
    }
}
