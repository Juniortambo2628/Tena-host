<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyTransfer;
use App\Services\PropertyTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin -> Transfers (docs/plans/property-transfer.md, step 3): every property
 * transfer, and holding, releasing or cancelling one during its cooling-off
 * (e.g. when an owner says they didn't offer it), and (step 5) reversing a
 * completed one within 30 days, once both owners agree in writing.
 */
class AdminTransferController extends Controller
{
    public function __construct(protected PropertyTransferService $transfers) {}

    public function index(): Response
    {
        PropertyTransfer::expireStale();
        $name = fn ($u) => $u ? (trim("{$u->first_name} {$u->last_name}") ?: $u->email) : null;

        $transfers = PropertyTransfer::with(['property:id,name', 'fromUser:id,first_name,last_name,email', 'toUser:id,first_name,last_name,email'])
            ->latest('id')->limit(500)->get()
            ->map(fn (PropertyTransfer $t) => [
                'id' => $t->id,
                'property' => $t->property?->name,
                'from' => $name($t->fromUser),
                'from_email' => $t->fromUser?->email,
                'to_email' => $t->to_email,
                'to' => $name($t->toUser),
                'status' => $t->status,
                'held' => (bool) $t->held_at,
                'created_at' => $t->created_at->toIso8601String(),
                'expires_at' => $t->expires_at->toIso8601String(),
                'decided_at' => $t->decided_at?->toIso8601String(),
                'completes_at' => $t->completes_at?->toIso8601String(),
                'completed_at' => $t->completed_at?->toIso8601String(),
                'cancelled_at' => $t->cancelled_at?->toIso8601String(),
                'cancelled_by' => $t->cancelled_by,
                'admin_note' => $t->admin_note,
                'from_ip' => $t->from_ip,
                'decided_ip' => $t->decided_ip,
                'moved' => $t->moved,
                'notice' => $t->notice_status ? ['status' => $t->notice_status, 'sent' => $t->notice_sent, 'total' => $t->notice_total] : null,
                'reversed_at' => $t->reversed_at?->toIso8601String(),
                'reversible' => $t->canBeReversed(),
                'devices' => $t->device_takeover ? ['count' => $t->device_count, 'balance' => $t->device_balance_kes, 'monthly' => $t->device_monthly_kes] : null,
            ]);

        return Inertia::render('Admin/Transfers/Index', [
            'transfers' => $transfers,
            'enabled' => PropertyTransferService::enabled(),
            'coolingOffHours' => PropertyTransfer::COOLING_OFF_HOURS,
            'reverseDays' => PropertyTransfer::REVERSE_DAYS,
        ]);
    }

    public function hold(Request $request, PropertyTransfer $transfer): RedirectResponse
    {
        $this->transfers->hold($transfer, $request->user(), $request->validate(['note' => 'nullable|string|max:400'])['note'] ?? null);

        return back()->with('success', 'Held. It won’t complete until you release it.');
    }

    public function release(PropertyTransfer $transfer): RedirectResponse
    {
        $this->transfers->release($transfer);

        return back()->with('success', 'Released. It completes when its cooling-off ends.');
    }

    public function cancel(Request $request, PropertyTransfer $transfer): RedirectResponse
    {
        $this->transfers->adminCancel($transfer, $request->user(), $request->validate(['note' => 'nullable|string|max:400'])['note'] ?? null);

        return back()->with('success', 'Transfer cancelled. Both owners have been told.');
    }

    public function reverse(Request $request, PropertyTransfer $transfer): RedirectResponse
    {
        $data = $request->validate([
            'note' => 'required|string|max:400',
            'agreed' => 'accepted',
        ], [
            'note.required' => 'Say where both owners agreed (e.g. the support ticket).',
            'agreed.accepted' => 'Tick that both owners agreed in writing.',
        ]);
        $this->transfers->reverse($transfer, $request->user(), $data['note']);

        return back()->with('success', 'Transfer reversed. The property is back with its previous owner, and both owners have been told.');
    }
}
