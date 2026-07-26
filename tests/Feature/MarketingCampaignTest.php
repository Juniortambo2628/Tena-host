<?php

use App\Models\Campaign;
use App\Models\Guest;
use App\Models\Property;
use App\Models\User;

it('persists audience and schedule data when creating a campaign', function () {
    $host = User::factory()->host()->create();
    $property = Property::factory()->create(['user_id' => $host->id]);
    Guest::factory()->create(['property_id' => $property->id]);

    $response = $this->actingAs($host)->post(route('host.marketing.store'), [
        'name' => 'Test Campaign',
        'type' => 'email',
        'subject' => 'Hello',
        'content' => 'Welcome to our property.',
        'target_audience' => 'property_guests',
        'audience_property_id' => $property->id,
        'audience_from' => now()->subYear()->toDateString(),
        'audience_to' => now()->toDateString(),
        'scheduled_at' => now()->addDay()->toDateTimeString(),
        'status' => 'draft',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $campaign = Campaign::where('name', 'Test Campaign')->first();
    expect($campaign)->not->toBeNull();
    expect($campaign->audience_property_id)->toBe($property->id);
    expect($campaign->audience_from)->not->toBeNull();
    expect($campaign->scheduled_at)->not->toBeNull();
});

it('activates a draft campaign', function () {
    $host = User::factory()->host()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $host->id,
        'status' => 'draft',
        'target_audience' => 'all_guests',
    ]);

    $response = $this->actingAs($host)->post(route('host.marketing.activate', $campaign));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($campaign->fresh()->status)->toBe('active');
});

it('prevents a host from activating another hosts campaign', function () {
    $host = User::factory()->host()->create();
    $otherHost = User::factory()->host()->create();
    $campaign = Campaign::factory()->create(['user_id' => $otherHost->id, 'status' => 'draft']);

    $response = $this->actingAs($host)->post(route('host.marketing.activate', $campaign));

    $response->assertForbidden();
});

it('allows pausing an active campaign', function () {
    $host = User::factory()->host()->create();
    $campaign = Campaign::factory()->create(['user_id' => $host->id, 'status' => 'active']);

    $response = $this->actingAs($host)->post(route('host.marketing.pause', $campaign));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($campaign->fresh()->status)->toBe('paused');
});
