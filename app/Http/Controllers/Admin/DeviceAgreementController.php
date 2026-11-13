<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceAgreement;
use App\Models\DeviceInstalment;
use App\Models\LandingPage;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin -> Devices: the device register (docs/plans/device-monthly-payments.md,
 * step 1). TenaFi devices paid for monthly, recorded by hand: the client, site
 * and serial number, the instalments, payments, status and a log.
 */
class DeviceAgreementController extends Controller
{
    public function index(): Response
    {
        $agreements = DeviceAgreement::with(['user:id,first_name,last_name,email,phone_number', 'property:id,name', 'instalments', 'events.user:id,first_name,last_name'])
            ->latest('id')->limit(500)->get()
            ->map(fn (DeviceAgreement $a) => [
                'id' => $a->id,
                'client' => trim("{$a->user?->first_name} {$a->user?->last_name}") ?: $a->user?->email,
                'contact' => $a->user?->phone_number ?: $a->user?->email,
                'site' => $a->property?->name,
                'device_type' => $a->device_type,
                'serial_number' => $a->serial_number,
                'mac_address' => $a->mac_address,
                'price_kes' => $a->price_kes,
                'monthly_kes' => $a->monthly_kes,
                'months' => $a->months,
                'starts_on' => $a->starts_on->toDateString(),
                'status' => $a->status,
                'paid_off_at' => $a->paid_off_at?->toDateString(),
                'notes' => $a->notes,
                'balance_kes' => $a->balance(),
                'next_due_on' => $a->nextDue()?->due_on->toDateString(),
                'days_overdue' => $a->daysOverdue(),
                'instalments' => $a->instalments->map(fn (DeviceInstalment $i) => [
                    'id' => $i->id,
                    'number' => $i->number,
                    'due_on' => $i->due_on->toDateString(),
                    'amount_kes' => $i->amount_kes,
                    'paid_kes' => $i->paid_kes,
                    'remaining_kes' => $i->remaining(),
                    'paid_at' => $i->paid_at?->toDateString(),
                    'reference' => $i->reference,
                    'status' => $i->status,
                ])->values(),
                'events' => $a->events->take(30)->map(fn ($e) => [
                    'id' => $e->id,
                    'type' => $e->type,
                    'note' => $e->note,
                    'meta' => $e->meta,
                    'by' => trim("{$e->user?->first_name} {$e->user?->last_name}") ?: null,
                    'at' => $e->created_at?->toIso8601String(),
                ])->values(),
            ]);

        $clients = User::where('role', 'host')->whereNull('team_owner_id')
            ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => trim("{$u->first_name} {$u->last_name}") ?: $u->email, 'email' => $u->email]);

        $terms = LandingPage::deviceInstalments();

        return Inertia::render('Admin/Devices/Index', [
            'agreements' => $agreements,
            'clients' => $clients,
            'sites' => Property::orderBy('name')->get(['id', 'name', 'user_id']),
            // Prices from /pricing (Admin -> Public Pages -> Devices & pricing), to fill the form,
            // with the instalment terms still stored there (the public pages no longer show them).
            'devices' => collect(LandingPage::liveDevices())->map(fn ($d) => collect($d)->only(['id', 'name', 'price_kes'])->all()
                + ($terms[$d['id']] ?? ['monthly_kes' => 0, 'months' => 12]))->values(),
            'statuses' => DeviceAgreement::STATUSES,
            'enabled' => DeviceAgreement::enabled(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('role', 'host')],
            // The site must be the client's own.
            'property_id' => ['nullable', Rule::exists('properties', 'id')->where('user_id', $request->integer('user_id'))],
            'device_type' => 'required|string|max:30',
            'serial_number' => 'nullable|string|max:80',
            'mac_address' => 'nullable|string|max:32',
            'price_kes' => 'required|integer|min:0|max:10000000',
            'monthly_kes' => 'required|integer|min:1|max:1000000',
            'months' => 'required|integer|min:1|max:36',
            'starts_on' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ], [
            'property_id.exists' => 'Pick one of this client’s sites.',
            'user_id.exists' => 'Pick a client.',
        ]);
        $data['mac_address'] = $data['mac_address'] ? strtolower(trim($data['mac_address'])) : null;

        $agreement = DeviceAgreement::record($data, $request->user());

        return back()->with('success', "Device recorded with {$agreement->months} monthly instalments of KES ".number_format($agreement->monthly_kes).'.');
    }

    public function pay(Request $request, DeviceAgreement $agreement, DeviceInstalment $instalment): RedirectResponse
    {
        abort_unless($instalment->device_agreement_id === $agreement->id, 404);
        abort_if($instalment->isSettled(), 422, 'This instalment is already settled.');

        $data = $request->validate([
            'amount_kes' => ['required', 'integer', 'min:1', 'max:'.$instalment->remaining()],
            'reference' => 'nullable|string|max:60',
        ], ['amount_kes.max' => 'That’s more than is left on this instalment (KES '.number_format($instalment->remaining()).').']);

        $agreement->recordPayment($instalment, (int) $data['amount_kes'], $data['reference'] ?? null, $request->user());

        return back()->with('success', 'Payment recorded.');
    }

    public function status(Request $request, DeviceAgreement $agreement): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(DeviceAgreement::STATUSES))],
            'note' => 'nullable|string|max:500',
        ]);

        $agreement->changeStatus($data['status'], $data['note'] ?? null, $request->user());

        return back()->with('success', 'Status changed to '.DeviceAgreement::STATUSES[$data['status']].'.');
    }
}
