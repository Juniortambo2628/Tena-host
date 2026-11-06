<?php

use App\Models\AccessPoint;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::setValue('billing_enabled', 'disabled', 'billing', 'string');
    $this->host = User::factory()->host()->create();
    $this->property = Property::factory()->create(['user_id' => $this->host->id]);
});

it('adds an access point and goes back to the list, whatever page came before', function () {
    $this->actingAs($this->host)->from('/some/json/endpoint')
        ->post(route('host.access-points.store'), ['property_id' => $this->property->id, 'name' => 'Lobby', 'mac_address' => 'A8-9C-6C-C4-D4-F7'])
        ->assertRedirect(route('host.access-points.index'));

    expect(AccessPoint::sole())->mac_address->toBe('a8:9c:6c:c4:d4:f7')->property_id->toBe($this->property->id);
});

it('lets the host remove their own access point, and nobody else’s', function () {
    $ap = AccessPoint::factory()->create(['property_id' => $this->property->id]);
    $theirs = AccessPoint::factory()->create(['property_id' => Property::factory()->create()->id]);

    $this->actingAs($this->host)->delete(route('host.access-points.destroy', $theirs))->assertForbidden();
    $this->actingAs($this->host)->delete(route('host.access-points.destroy', $ap))
        ->assertRedirect(route('host.access-points.index'));

    expect(AccessPoint::find($ap->id))->toBeNull()->and(AccessPoint::find($theirs->id))->not->toBeNull();
});

it('returns to the access point after changing it', function () {
    $ap = AccessPoint::factory()->create(['property_id' => $this->property->id, 'status' => 'offline']);

    $this->actingAs($this->host)->patch(route('host.access-points.update', $ap), ['status' => 'online'])
        ->assertRedirect(route('host.access-points.show', $ap));
    expect($ap->fresh()->status)->toBe('online');
});
