<?php

namespace App\Models;

use App\Services\Billing\PlanPricing;
use App\Traits\BelongsToHost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A dated event at a venue (a property): a launch, a conference day, a
 * wedding, a match. Attendees are the venue's guests who connected while it
 * ran (event_guest).
 */
class Event extends Model
{
    use BelongsToHost, HasFactory;

    protected $fillable = ['user_id', 'property_id', 'name', 'starts_on', 'ends_on', 'branding_json', 'question', 'sponsor'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'branding_json' => 'array',
        'sponsor' => 'array',
        'expected_attendees' => 'integer',
        'quote_amount' => 'integer',
        'quote_requested_at' => 'datetime',
        'quoted_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /** Admin -> Settings -> Pay per event is on (it starts off). */
    public static function billingEnabled(): bool
    {
        return filter_var(Setting::getValue('event_billing_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Whether this event still has to be paid for before its WiFi page goes
     * live: pay per event is on, billing is live, the organiser's plan (or
     * trial) doesn't cover it and it hasn't been paid.
     */
    public function needsPayment(): bool
    {
        if ($this->paid_at || ! static::billingEnabled() || ! PlanPricing::enforced()) {
            return false;
        }

        return ! $this->ownerOnPlan();
    }

    /** The organiser's monthly plan (or trial) is active. */
    public function ownerOnPlan(): bool
    {
        $owner = $this->owner?->accountOwner();

        return (bool) $owner && ($owner->subscribed('default') || $owner->onTrial());
    }

    /** A confirmed payment for the quote. */
    public function markPaid(): void
    {
        $this->forceFill(['quote_status' => 'paid', 'paid_at' => now()])->save();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class)
            ->using(EventAttendance::class)
            ->withPivot(['days', 'answer', 'first_seen_at', 'last_seen_at'])
            ->withTimestamps();
    }

    /** The event running at this venue today, if any (the latest to start when they overlap). */
    public static function runningAt(Property $property, ?Carbon $today = null): ?self
    {
        $day = ($today ?? now())->toDateString();

        // An event awaiting payment (pay per event) leaves the venue's usual page.
        return static::where('property_id', $property->id)
            ->whereDate('starts_on', '<=', $day)->whereDate('ends_on', '>=', $day)
            ->orderByDesc('starts_on')->get()
            ->first(fn (self $event) => ! $event->needsPayment());
    }

    /**
     * A sign-in during the event: add the attendee (or today to their days)
     * and keep their latest answer to the event's question.
     */
    public function recordAttendance(Guest $guest, ?string $answer = null, ?Carbon $at = null): void
    {
        $at ??= now();
        $existing = $this->attendees()->where('guests.id', $guest->id)->first()?->pivot;
        $days = collect($existing?->days ?? [])->push($at->toDateString())->unique()->sort()->values()->all();
        $values = [
            'days' => $days,
            'answer' => filled($answer) ? $answer : $existing?->answer,
            'first_seen_at' => $existing?->first_seen_at ?? $at,
            'last_seen_at' => $at,
        ];

        $existing
            ? $this->attendees()->updateExistingPivot($guest->id, $values)
            : $this->attendees()->attach($guest->id, $values);
    }

    /**
     * The sponsor report: how many saw the sponsor, tapped their link and
     * answered the question, and the most common answers.
     *
     * @return array{views: int, clicks: int, attendees: int, answered: int, topAnswers: list<array{answer: string, count: int}>}
     */
    public function sponsorReport(): array
    {
        $answers = EventAttendance::where('event_id', $this->id)->whereNotNull('answer')->where('answer', '!=', '')->pluck('answer');

        return [
            'views' => Analytics::total($this->metric('sponsor_views')),
            'clicks' => Analytics::total($this->metric('sponsor_clicks')),
            'attendees' => $this->attendees()->count(),
            'answered' => $answers->count(),
            'topAnswers' => $answers->map(fn ($a) => trim($a))
                ->groupBy(fn ($a) => mb_strtolower($a))
                ->map(fn ($group) => ['answer' => $group->first(), 'count' => $group->count()])
                ->sortByDesc('count')->take(5)->values()->all(),
        ];
    }

    /** Sponsor report counters: sponsor_views (WiFi page shown with the sponsor) and sponsor_clicks. */
    public function metric(string $name): string
    {
        return "event.{$this->id}.{$name}";
    }

    /** The WiFi page's welcome during the event: its own heading and message, else "Welcome to <event>". */
    public function welcome(): array
    {
        return [
            'heading' => ($this->branding_json['heading'] ?? null) ?: "Welcome to {$this->name}",
            'message' => ($this->branding_json['message'] ?? null) ?: null,
        ];
    }

    /** upcoming, live (running today) or past. */
    public function status(?Carbon $today = null): string
    {
        $today = ($today ?? now())->copy()->startOfDay();

        return match (true) {
            $this->starts_on->gt($today) => 'upcoming',
            $this->ends_on->lt($today) => 'past',
            default => 'live',
        };
    }
}
