<?php

use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * The event features the public pages describe but the product doesn't
 * have yet get a "Coming soon" badge: sponsors, the extra question, and a
 * page per event with attendees tagged across events. Flip each to "live"
 * in Admin → Public Pages → Site-wide → Feature status when it ships.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::addFeatureStatus('event_sponsors', 'Event sponsors: branding, offer and report');
        PageBlueprint::addFeatureStatus('event_question', 'An extra question on the WiFi page');
        PageBlueprint::addFeatureStatus('event_tagging', 'Events: a page per event or day, attendees tagged across events');

        PageBlueprint::tagFeatures([
            'Sponsor logo, question and offer on the first screen' => 'event_sponsors',
            'Sponsor logo, colours and offer on the WiFi page' => 'event_sponsors',
            'A report of views, answers and clicks, not a guess' => 'event_sponsors',
            'Change the sponsor by day, stage or match' => 'event_sponsors',
            'Give sponsors something to measure' => 'event_sponsors',
            'Name, phone and email, plus one question of your choice' => 'event_question',
            'A question the sponsor wants answered' => 'event_question',
            'Attendees tagged by event, ready for the next on-sale' => 'event_tagging',
            'New and returning attendees, across all your events' => 'event_tagging',
            'A WiFi page per event, day, stage or fixture' => 'event_tagging',
            'Your next on-sale goes to people who already showed up' => 'event_tagging',
            'See who comes back, event after event' => 'event_tagging',
            'Early-bird tickets for past attendees' => 'event_tagging',
            'Every event its own sign-in' => 'event_tagging',
        ]);
    }

    public function down(): void
    {
        //
    }
};
