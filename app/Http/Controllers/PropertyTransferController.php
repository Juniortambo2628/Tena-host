<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\PropertyTransfer;
use App\Services\PropertyTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ];

        $incoming = PropertyTransfer::incomingFor($user)->with(['property', 'fromUser:id,first_name,last_name,email'])->latest('id')->limit(50)->get();

        return Inertia::render('Host/Transfers/Index', [
            'properties' => $user->properties()->orderBy('name')->get(['id', 'name']),
            'outgoing' => PropertyTransfer::where('from_user_id', $user->id)->with(['property', 'fromUser:id,first_name,last_name,email'])->latest('id')->limit(50)->get()->map($row),
            // Waiting offers show what would move, so the recipient knows what they're accepting.
            'incoming' => $incoming->map(fn (PropertyTransfer $t) => $row($t) + ($t->isPending() && $t->property ? ['preview' => [
                'guests' => $t->property->guests()->count(),
                'access_points' => $t->property->accessPoints()->count(),
                'campaigns' => Campaign::where('property_id', $t->property_id)->count(),
            ]] : [])),
            'validDays' => PropertyTransfer::VALID_DAYS,
            'holdDays' => PropertyTransfer::CAMPAIGN_HOLD_DAYS,
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
        ], [
            'property_id.exists' => 'Pick one of your own properties.',
            'password.current_password' => 'That password isn’t right.',
        ]);

        $property = $user->properties()->findOrFail($data['property_id']);
        $this->transfers->start($user, $property, $data['email'], $request->ip());

        return back()->with('success', "Transfer offered to {$data['email']}. They have ".PropertyTransfer::VALID_DAYS.' days to accept; you can cancel until then.');
    }

    public function accept(Request $request, PropertyTransfer $transfer): RedirectResponse
    {
        abort_unless(PropertyTransferService::enabled(), 404);
        $this->transfers->accept($transfer, $request->user(), $request->ip());

        return redirect()->route('host.properties.index')->with('success', "{$transfer->property->name} is now yours.");
    }

    public function decline(Request $request, PropertyTransfer $transfer): RedirectResponse
    {
        abort_unless(PropertyTransferService::enabled(), 404);
        $this->transfers->decline($transfer, $request->user(), $request->ip());

        return back()->with('success', 'Transfer declined.');
    }

    public function cancel(Request $request, PropertyTransfer $transfer): RedirectResponse
    {
        abort_unless(PropertyTransferService::enabled(), 404);
        $this->transfers->cancel($transfer, $request->user(), $request->ip());

        return back()->with('success', 'Transfer cancelled. The property stays yours.');
    }
}
