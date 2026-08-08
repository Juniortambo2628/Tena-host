<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

/*
|--------------------------------------------------------------------------
| Authentication Tests
|--------------------------------------------------------------------------
*/

it('renders the login page successfully', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

it('allows a user to login with valid credentials', function () {
    $user = User::factory()->host()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

it('does not allow login with invalid credentials', function () {
    $user = User::factory()->host()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'wrong@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('does not allow login with wrong password', function () {
    $user = User::factory()->host()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('redirects user to dashboard after login', function () {
    $user = User::factory()->host()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
});

it('allows a user to logout', function () {
    $user = User::factory()->host()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect('/');
    $this->assertGuest();
});

/*
|--------------------------------------------------------------------------
| Registration Tests
|--------------------------------------------------------------------------
*/

it('renders the registration page successfully', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Auth/Register'));
});

it('allows a user to register with valid data', function () {
    $response = $this->post(route('register'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    $this->assertAuthenticated();
});

it('validates required fields on registration', function () {
    $response = $this->post(route('register'), [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'password' => '',
    ]);

    $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password']);
});

it('validates email uniqueness on registration', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->post(route('register'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'existing@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('email');
});

it('validates password confirmation on registration', function () {
    $response = $this->post(route('register'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors('password');
});

/*
|--------------------------------------------------------------------------
| Password Reset Tests
|--------------------------------------------------------------------------
*/

it('renders the forgot password page successfully', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
});

it('sends a password reset link for a valid email', function () {
    Notification::fake();

    $user = User::factory()->host()->create();

    $response = $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class);
});

it('renders the password reset page with a valid token', function () {
    $user = User::factory()->host()->create();

    $token = Password::broker()->createToken($user);

    $response = $this->get(route('password.reset', $token));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Auth/ResetPassword'));
});

it('allows a user to reset their password with a valid token', function () {
    $user = User::factory()->host()->create();

    $token = Password::broker()->createToken($user);

    $response = $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
});

it('fails to reset password with an invalid token', function () {
    $user = User::factory()->host()->create();

    $response = $this->post(route('password.store'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertSessionHasErrors('email');
});

/*
|--------------------------------------------------------------------------
| Auth Middleware Tests
|--------------------------------------------------------------------------
*/

it('redirects unauthenticated user to login', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Profile Tests
|--------------------------------------------------------------------------
*/

it('renders the profile page for an authenticated user', function () {
    $user = User::factory()->host()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Profile/Edit'));
});

it('allows a user to update their profile name', function () {
    $user = User::factory()->host()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'first_name' => 'New',
        'last_name' => 'User',
        'email' => $user->email,
    ]);

    $response->assertRedirect(route('profile.edit'));
    $user->refresh();
    expect($user->first_name)->toBe('New');
    expect($user->last_name)->toBe('User');
});

it('allows a user to update their profile email', function () {
    $user = User::factory()->host()->create([
        'email' => 'old@example.com',
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => 'new@example.com',
    ]);

    $response->assertRedirect(route('profile.edit'));
    $user->refresh();
    expect($user->email)->toBe('new@example.com');
    expect($user->email_verified_at)->toBeNull();
});

it('validates required fields on profile update', function () {
    $user = User::factory()->host()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
    ]);

    $response->assertSessionHasErrors(['first_name', 'last_name', 'email']);
});

it('validates email format on profile update', function () {
    $user = User::factory()->host()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

it('allows a user to delete their account', function () {
    $user = User::factory()->host()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertGuest();
});

it('requires password confirmation to delete account', function () {
    $user = User::factory()->host()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => '',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

it('does not allow account deletion with wrong password', function () {
    $user = User::factory()->host()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
