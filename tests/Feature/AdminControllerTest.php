<?php

use App\Models\LandingContent;
use App\Models\LandingSection;
use App\Models\PolicyDocument;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Admin Dashboard
|--------------------------------------------------------------------------
*/

it('allows an admin to access the admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
});

it('denies a non-admin from accessing the admin dashboard', function () {
    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Host Management
|--------------------------------------------------------------------------
*/

it('allows an admin to list hosts', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.hosts.index'));

    $response->assertOk();
});

it('allows an admin to view a host', function () {
    $admin = User::factory()->admin()->create();
    $host = User::factory()->host()->create();

    $response = $this->actingAs($admin)->get(route('admin.hosts.show', $host));

    $response->assertOk();
});

it('allows an admin to update a host', function () {
    $admin = User::factory()->admin()->create();
    $host = User::factory()->host()->create(['first_name' => 'OldName']);

    $response = $this->actingAs($admin)->put(route('admin.hosts.update', $host), [
        'first_name' => 'NewName',
    ]);

    $response->assertRedirect();
    $host->refresh();
    expect($host->first_name)->toBe('NewName');
});

it('allows an admin to delete a host', function () {
    $admin = User::factory()->admin()->create();
    $host = User::factory()->host()->create();

    $response = $this->actingAs($admin)->delete(route('admin.hosts.destroy', $host));

    $response->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $host->id]);
});

it('prevents an admin from deleting themselves via host delete', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->delete(route('admin.hosts.destroy', $admin));

    $response->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

/*
|--------------------------------------------------------------------------
| User Management
|--------------------------------------------------------------------------
*/

it('allows an admin to list users', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
});

it('allows an admin to view a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->guest()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

    $response->assertOk();
});

it('allows an admin to update a user role', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->guest()->create(['role' => 'guest']);

    $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'role' => 'host',
    ]);

    $response->assertRedirect();
    $user->refresh();
    expect($user->role)->toBe('host');
});

it('allows an admin to delete a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->guest()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

    $response->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('prevents an admin from deleting themselves via user delete', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

    $response->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

/*
|--------------------------------------------------------------------------
| Policy Documents
|--------------------------------------------------------------------------
*/

it('allows an admin to list policies', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.policies.index'));

    $response->assertOk();
});

it('allows an admin to create a policy', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.policies.store'), [
        'title' => 'Test Privacy Policy',
        'content' => 'This is the privacy policy content.',
        'type' => 'privacy_policy',
    ]);

    $response->assertRedirect(route('admin.policies.index'));
    $this->assertDatabaseHas('policy_documents', ['title' => 'Test Privacy Policy']);
});

it('allows an admin to view a policy', function () {
    $admin = User::factory()->admin()->create();
    $slug = 'test-view-policy-'.Str::random(8);
    $policy = PolicyDocument::create([
        'title' => 'Test Terms of Service',
        'slug' => $slug,
        'content' => 'Terms content here.',
        'type' => 'terms_of_service',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.policies.show', $policy));

    $response->assertOk();
});

it('allows an admin to update a policy', function () {
    $admin = User::factory()->admin()->create();
    $slug = 'test-update-policy-'.Str::random(8);
    $policy = PolicyDocument::create([
        'title' => 'Test Cookie Policy',
        'slug' => $slug,
        'content' => 'Cookie content.',
        'type' => 'cookie_policy',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.policies.update', $policy), [
        'title' => 'Updated Cookie Policy',
        'content' => 'Updated content.',
        'type' => 'cookie_policy',
    ]);

    $response->assertRedirect(route('admin.policies.index'));
    $policy->refresh();
    expect($policy->title)->toBe('Updated Cookie Policy');
});

it('allows an admin to delete a policy', function () {
    $admin = User::factory()->admin()->create();
    $slug = 'test-delete-policy-'.Str::random(8);
    $policy = PolicyDocument::create([
        'title' => 'Delete Me',
        'slug' => $slug,
        'content' => 'To be deleted.',
        'type' => 'other',
    ]);

    $response = $this->actingAs($admin)->delete(route('admin.policies.destroy', $policy));

    $response->assertRedirect(route('admin.policies.index'));
    $this->assertDatabaseMissing('policy_documents', ['id' => $policy->id]);
});

it('allows an admin to toggle policy publish status', function () {
    $admin = User::factory()->admin()->create();
    $slug = 'test-toggle-policy-'.Str::random(8);
    $policy = PolicyDocument::create([
        'title' => 'Toggle Policy',
        'slug' => $slug,
        'content' => 'Toggle content.',
        'type' => 'other',
        'is_published' => false,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.policies.toggle', $policy));

    $response->assertRedirect();
    $policy->refresh();
    expect($policy->is_published)->toBeTrue();
});

it('validates required fields on policy creation', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.policies.store'), [
        'title' => '',
        'content' => '',
        'type' => '',
    ]);

    $response->assertSessionHasErrors(['title', 'content', 'type']);
});

it('denies a non-admin from managing policies', function () {
    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->get(route('admin.policies.index'));

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Registrations
|--------------------------------------------------------------------------
*/

it('allows an admin to list registrations', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.registrations.index'));

    $response->assertOk();
});

it('allows an admin to update a registration status', function () {
    $admin = User::factory()->admin()->create();
    $registration = Registration::factory()->create(['status' => 'active']);

    $response = $this->actingAs($admin)->put(route('admin.registrations.update', $registration), [
        'status' => 'converted',
    ]);

    $response->assertRedirect();
    $registration->refresh();
    expect($registration->status)->toBe('converted');
});

it('allows an admin to delete a registration', function () {
    $admin = User::factory()->admin()->create();
    $registration = Registration::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.registrations.destroy', $registration));

    $response->assertRedirect();
    $this->assertDatabaseMissing('registrations', ['id' => $registration->id]);
});

it('denies a non-admin from managing registrations', function () {
    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->get(route('admin.registrations.index'));

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

it('allows an admin to view settings', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.settings.index'));

    $response->assertOk();
});

it('allows an admin to update settings', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
        'settings' => [
            'site_name' => 'Tena Test',
        ],
    ]);

    $response->assertRedirect();
    expect(Setting::getValue('site_name'))->toBe('Tena Test');
});

it('allows an admin to update email template settings', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
        'settings' => [
            'welcome_email_heading' => 'Welcome to Tena',
            'welcome_email_body' => 'Thanks for signing up.',
        ],
    ]);

    $response->assertRedirect();
    expect(Setting::getValue('welcome_email_heading'))->toBe('Welcome to Tena');
    expect(Setting::getValue('welcome_email_body'))->toBe('Thanks for signing up.');
});

it('denies a non-admin from updating settings', function () {
    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->post(route('admin.settings.update'), [
        'settings' => [
            'site_name' => 'Hacked',
        ],
    ]);

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Landing CMS
|--------------------------------------------------------------------------
*/

it('allows an admin to view the landing editor', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.landing.index'));

    $response->assertOk();
});

it('allows an admin to update a landing section', function () {
    $admin = User::factory()->admin()->create();
    $section = LandingSection::create([
        'section_key' => 'hero',
        'title' => 'Old Title',
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.landing.sections.update', $section), [
        'title' => 'New Title',
    ]);

    $response->assertRedirect();
    $section->refresh();
    expect($section->title)->toBe('New Title');
});

it('allows an admin to reorder landing sections', function () {
    $admin = User::factory()->admin()->create();
    $sectionA = LandingSection::create(['section_key' => 'a', 'sort_order' => 0]);
    $sectionB = LandingSection::create(['section_key' => 'b', 'sort_order' => 1]);

    $response = $this->actingAs($admin)->post(route('admin.landing.sections.reorder'), [
        'order' => [$sectionB->id, $sectionA->id],
    ]);

    $response->assertRedirect();
    $sectionA->refresh();
    $sectionB->refresh();
    expect($sectionB->sort_order)->toBe(0);
    expect($sectionA->sort_order)->toBe(1);
});

it('allows an admin to manage landing content', function () {
    $admin = User::factory()->admin()->create();
    $section = LandingSection::create([
        'section_key' => 'features',
        'title' => 'Features',
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.landing.content.store'), [
        'section_id' => $section->id,
        'content_key' => 'headline',
        'value' => 'Great Features',
        'type' => 'text',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('landing_content', [
        'section_id' => $section->id,
        'content_key' => 'headline',
        'value' => 'Great Features',
    ]);
});

/*
|--------------------------------------------------------------------------
| System
|--------------------------------------------------------------------------
*/

it('allows an admin to view system info', function () {
    $admin = User::factory()->admin()->create();

    $mockObj = new \stdClass();
    $mockObj->size_mb = 1.5;

    \Illuminate\Support\Facades\DB::shouldReceive('select')
        ->once()
        ->andReturn([$mockObj]);

    $response = $this->actingAs($admin)->get(route('admin.system.index'));

    $response->assertOk();
});

it('denies a non-admin from viewing system info', function () {
    $host = User::factory()->host()->create();

    $response = $this->actingAs($host)->get(route('admin.system.index'));

    $response->assertForbidden();
});
