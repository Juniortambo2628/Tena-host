<?php

namespace Tests\Feature;

use App\Mail\UserInvitationMail;
use App\Models\Registration;
use App\Models\User;
use App\Services\SignupConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SignupConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['services.whatsapp' => [
            'driver' => 'whatsapp_cloud', 'token' => 't', 'phone_number_id' => '1', 'template' => null,
            'language' => 'en', 'api_version' => 'v21.0', 'fallback_to_sms' => false,
        ]]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
    }

    private function signup(array $attributes = []): Registration
    {
        return Registration::factory()->create(array_merge([
            'type' => 'host',
            'first_name' => 'Amina',
            'email' => null,
            'phone' => '+254712345678',
            'business_name' => 'Amina Apartments',
            'location' => 'Kilimani',
            'units' => '10-49',
            'answers' => ['plan' => 'growth', 'units' => '10-49'],
            'status' => 'active',
        ], $attributes));
    }

    public function test_admin_converts_a_phone_only_signup_and_invites_on_whatsapp(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $signup = $this->signup();

        $this->actingAs($admin)
            ->post(route('admin.registrations.convert', $signup), ['channels' => ['email', 'whatsapp']])
            ->assertSessionHas('success', 'Account ready for Amina. Invite sent by whatsapp.');

        $user = User::where('phone_number', '+254712345678')->sole();
        $this->assertNull($user->email);
        $this->assertSame('host', $user->role);
        $this->assertSame('growth', $user->billing_plan);
        $this->assertSame(10, $user->billing_units);
        $this->assertSame('Amina Apartments', $user->properties()->sole()->name);
        $this->assertSame(['converted', $user->id], [$signup->fresh()->status, $signup->fresh()->user_id]);
        Mail::assertNothingSent();
        Http::assertSent(fn (Request $r) => str_contains($r['text']['body'], '/invitation/'.$user->id.'?expires='));
    }

    public function test_converting_again_resends_without_duplicating(): void
    {
        $signup = $this->signup(['email' => 'amina@example.com']);
        $service = app(SignupConversionService::class);

        $service->convert($signup, ['email']);
        $service->convert($signup->fresh(), ['email']);

        $this->assertSame(1, User::count());
        $this->assertSame(1, User::sole()->properties()->count());
        Mail::assertSent(UserInvitationMail::class, 2);
    }

    public function test_existing_user_is_linked_not_duplicated(): void
    {
        $existing = User::factory()->create(['email' => 'amina@example.com', 'role' => 'host']);

        ['user' => $user] = app(SignupConversionService::class)->convert($this->signup(['email' => 'amina@example.com']), ['email']);

        $this->assertTrue($user->is($existing));
    }

    public function test_invite_link_sets_password_and_signs_in(): void
    {
        ['user' => $user] = app(SignupConversionService::class)->convert($this->signup(), ['whatsapp']);
        $link = URL::temporarySignedRoute('invitation.show', now()->addDay(), ['user' => $user->id]);

        $this->get($link)->assertOk()->assertInertia(fn ($page) => $page
            ->component('Auth/ResetPassword')
            ->where('email', '+254712345678')
            ->where('invitation.name', 'Amina'));

        $this->post($link, ['password' => 'Karibu-2026!', 'password_confirmation' => 'Karibu-2026!'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_unsigned_or_expired_invites_are_rejected(): void
    {
        ['user' => $user] = app(SignupConversionService::class)->convert($this->signup(), ['whatsapp']);

        $this->get('/invitation/'.$user->id)->assertForbidden();
        $expired = URL::temporarySignedRoute('invitation.show', now()->subMinute(), ['user' => $user->id]);
        $this->get($expired)->assertForbidden();
    }

    public function test_phone_only_user_signs_in_with_their_number(): void
    {
        $user = User::factory()->create(['email' => null, 'phone_number' => '+254712345678', 'password' => bcrypt('secret-pass'), 'role' => 'host']);

        $this->post('/login', ['email' => '0712 345 678', 'password' => 'secret-pass']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_email_sign_in_still_works(): void
    {
        $user = User::factory()->create(['email' => 'host@example.com', 'password' => bcrypt('secret-pass'), 'role' => 'host']);

        $this->post('/login', ['email' => 'host@example.com', 'password' => 'secret-pass']);

        $this->assertAuthenticatedAs($user);
    }
}
