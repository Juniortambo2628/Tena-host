<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Services\SignupConversionService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The account owner's team: people they invite, each with their own login
 * and only the sites they're given. A manager uses the dashboard for those
 * sites (billing, marketing and adding sites stay with the owner); staff
 * get the staff dashboard. Off until Admin -> Settings switches it on.
 */
class TeamController extends Controller
{
    /** team_role => the login's role. */
    public const ROLES = ['manager' => 'host', 'staff' => 'staff'];

    public static function enabled(): bool
    {
        return filter_var(Setting::getValue('teams_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function index(Request $request): Response
    {
        abort_unless(static::enabled(), 404);
        $owner = $request->user();

        return Inertia::render('Host/Team/Index', [
            'members' => $owner->teamMembers()->with('staffProperties:id')->orderBy('first_name')->get()
                ->map(fn (User $m) => [
                    'id' => $m->id,
                    'name' => trim("{$m->first_name} {$m->last_name}"),
                    'email' => $m->email,
                    'phone' => $m->phone_number,
                    'team_role' => $m->team_role,
                    'sites' => $m->staffProperties->pluck('id')->all(),
                    'joined' => $m->last_login !== null,
                ]),
            'sites' => $owner->properties()->orderBy('name')->get(['id', 'name', 'region']),
        ]);
    }

    public function store(Request $request, SignupConversionService $invites): RedirectResponse
    {
        abort_unless(static::enabled(), 404);
        $owner = $request->user();
        $request->merge(['phone' => Phone::toE164($request->input('phone'))]);

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|required_without:phone|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone_number',
            ...$this->accessRules($owner),
        ], [
            'email.required_without' => 'Add an email or a WhatsApp number to send the invite to.',
            'email.unique' => 'This email already has a TenaFi login.',
            'phone.unique' => 'This number already has a TenaFi login.',
        ]);

        $member = User::create([
            'username' => Str::slug($data['first_name']).'-'.Str::lower(Str::random(6)),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'] ? strtolower($data['email']) : null,
            'phone_number' => $data['phone'],
            'role' => self::ROLES[$data['team_role']],
            'account_type' => $owner->account_type,
            'team_owner_id' => $owner->id,
            'team_role' => $data['team_role'],
            'password' => Hash::make(Str::random(40)),
        ]);
        $member->staffProperties()->sync($data['sites']);
        $member->clearPropertyIdsCache();

        $sent = $invites->invite($member, ['email', 'whatsapp']);

        return back()->with($sent ? 'success' : 'error', $sent
            ? "{$member->first_name} is on your team. Invite sent by ".implode(' and ', array_unique($sent)).'.'
            : "{$member->first_name} is on your team, but the invite couldn't be sent. Check their email or number.");
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        abort_unless(static::enabled(), 404);
        $owner = $request->user();
        abort_unless($member->team_owner_id === $owner->id, 404);

        $data = $request->validate($this->accessRules($owner));

        $member->update(['team_role' => $data['team_role'], 'role' => self::ROLES[$data['team_role']]]);
        $member->staffProperties()->sync($data['sites']);
        $member->clearPropertyIdsCache();

        return back()->with('success', "{$member->first_name}'s access is updated.");
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        abort_unless(static::enabled(), 404);
        abort_unless($member->team_owner_id === $request->user()->id, 404);

        $name = $member->first_name;
        $member->delete();

        return back()->with('success', "{$name} is no longer on your team.");
    }

    /** A role, and at least one of the owner's own sites. */
    private function accessRules(User $owner): array
    {
        return [
            'team_role' => ['required', Rule::in(array_keys(self::ROLES))],
            'sites' => 'required|array|min:1',
            'sites.*' => ['integer', Rule::in($owner->properties()->pluck('id')->all())],
        ];
    }
}
