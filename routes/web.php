<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HostDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'landingContent' => \App\Http\Controllers\Admin\LandingController::getPublicData(),
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');

    // Admin Features
    Route::get('/hosts', [\App\Http\Controllers\Admin\HostController::class, 'index'])->name('hosts.index');
    Route::get('/hosts/{user}', [\App\Http\Controllers\Admin\HostController::class, 'show'])->name('hosts.show');
    Route::put('/hosts/{user}', [\App\Http\Controllers\Admin\HostController::class, 'update'])->name('hosts.update');
    Route::delete('/hosts/{user}', [\App\Http\Controllers\Admin\HostController::class, 'destroy'])->name('hosts.destroy');

    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/system', [\App\Http\Controllers\Admin\SystemController::class, 'index'])->name('system.index');
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');

    // Registrations
    Route::get('/registrations', [\App\Http\Controllers\Admin\RegistrationController::class, 'index'])->name('registrations.index');
    Route::put('/registrations/{registration}', [\App\Http\Controllers\Admin\RegistrationController::class, 'update'])->name('registrations.update');
    Route::delete('/registrations/{registration}', [\App\Http\Controllers\Admin\RegistrationController::class, 'destroy'])->name('registrations.destroy');

    // Landing Page CMS
    Route::get('/landing', [\App\Http\Controllers\Admin\LandingController::class, 'index'])->name('landing.index');
    Route::put('/landing/sections/{section}', [\App\Http\Controllers\Admin\LandingController::class, 'updateSection'])->name('landing.sections.update');
    Route::post('/landing/sections/reorder', [\App\Http\Controllers\Admin\LandingController::class, 'reorder'])->name('landing.sections.reorder');
    Route::post('/landing/content', [\App\Http\Controllers\Admin\LandingController::class, 'storeContent'])->name('landing.content.store');
    Route::put('/landing/content', [\App\Http\Controllers\Admin\LandingController::class, 'updateContent'])->name('landing.content.update');
    Route::delete('/landing/content/{content}', [\App\Http\Controllers\Admin\LandingController::class, 'destroyContent'])->name('landing.content.destroy');
    Route::post('/landing/sections/{section}/media', [\App\Http\Controllers\Admin\LandingController::class, 'uploadMedia'])->name('landing.media.upload');
    Route::post('/landing/sections/{section}/media/assign', [\App\Http\Controllers\Admin\LandingController::class, 'assignMedia'])->name('landing.media.assign');
    Route::get('/landing/media/all', [\App\Http\Controllers\Admin\LandingController::class, 'listMedia'])->name('landing.media.list');
    Route::put('/landing/media/{media}/crop', [\App\Http\Controllers\Admin\LandingController::class, 'updateCrop'])->name('landing.media.crop');
    Route::delete('/landing/media/{media}', [\App\Http\Controllers\Admin\LandingController::class, 'destroyMedia'])->name('landing.media.destroy');
    Route::get('/landing/media/{media}/download', [\App\Http\Controllers\Admin\LandingController::class, 'downloadMedia'])->name('landing.media.download');
});

// Host Routes
Route::middleware(['auth', 'verified', 'host'])->prefix('host')->name('host.')->group(function () {
    // Billing & Payments (Accessible to all authenticated hosts)
    Route::get('/billing', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('billing.index');
    Route::post('/billing/stripe', [\App\Http\Controllers\SubscriptionController::class, 'storeStripe'])->name('billing.stripe');
    Route::post('/billing/mpesa', [\App\Http\Controllers\SubscriptionController::class, 'storeMpesa'])->name('billing.mpesa');
    Route::post('/billing/simulate', [\App\Http\Controllers\SubscriptionController::class, 'simulateMpesa'])->name('billing.simulate');

    // Protected Host Routes (Requires Subscription)
    Route::middleware([\App\Http\Middleware\EnsureUserIsSubscribed::class])->group(function () {
        Route::get('/dashboard', [HostDashboardController::class, 'index'])->name('dashboard');

        // Properties
        Route::resource('properties', PropertyController::class);

        // Access Points
        Route::get('/access-points', [\App\Http\Controllers\AccessPointController::class, 'index'])->name('access-points.index');
        Route::post('/access-points', [\App\Http\Controllers\AccessPointController::class, 'store'])->name('access-points.store');
        Route::get('/access-points/{accessPoint}', [\App\Http\Controllers\AccessPointController::class, 'show'])->name('access-points.show');
        Route::put('/access-points/{accessPoint}', [\App\Http\Controllers\AccessPointController::class, 'update'])->name('access-points.update');
        Route::delete('/access-points/{accessPoint}', [\App\Http\Controllers\AccessPointController::class, 'destroy'])->name('access-points.destroy');

        // Amenities
        Route::get('/amenities', [\App\Http\Controllers\AmenityController::class, 'index'])->name('amenities.index');
        Route::post('/amenities', [\App\Http\Controllers\AmenityController::class, 'store'])->name('amenities.store');
        Route::get('/amenities/{amenity}', [\App\Http\Controllers\AmenityController::class, 'show'])->name('amenities.show');
        Route::put('/amenities/{amenity}', [\App\Http\Controllers\AmenityController::class, 'update'])->name('amenities.update');
        Route::delete('/amenities/{amenity}', [\App\Http\Controllers\AmenityController::class, 'destroy'])->name('amenities.destroy');

        // Orders
        Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'destroy'])->name('orders.destroy');

        // Guests
        Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
        Route::post('/guests', [GuestController::class, 'store'])->name('guests.store');
        Route::get('/guests/{guest}', [GuestController::class, 'show'])->name('guests.show');
        Route::put('/guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');

        // Marketing
        Route::get('/marketing', [\App\Http\Controllers\MarketingController::class, 'index'])->name('marketing.index');
        Route::get('/marketing/create', [\App\Http\Controllers\MarketingController::class, 'create'])->name('marketing.builder');
        Route::post('/marketing', [\App\Http\Controllers\MarketingController::class, 'store'])->name('marketing.store');
        Route::get('/marketing/{id}/edit', [\App\Http\Controllers\MarketingController::class, 'edit'])->name('marketing.edit');
        Route::put('/marketing/{id}', [\App\Http\Controllers\MarketingController::class, 'update'])->name('marketing.update');
        Route::delete('/marketing/{id}', [\App\Http\Controllers\MarketingController::class, 'destroy'])->name('marketing.destroy');
        Route::get('/marketing/{id}/analytics', [\App\Http\Controllers\MarketingController::class, 'analytics'])->name('marketing.analytics');
        Route::post('/marketing/{id}/activate', [\App\Http\Controllers\MarketingController::class, 'activate'])->name('marketing.activate');
        Route::post('/marketing/{id}/pause', [\App\Http\Controllers\MarketingController::class, 'pause'])->name('marketing.pause');
    });
});

// Staff Routes
Route::middleware(['auth', 'verified', 'staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Staff\StaffDashboardController::class, 'index'])->name('dashboard');
});
Route::prefix('g')->name('guest.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\GuestOtpController::class, 'create'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\GuestOtpController::class, 'store'])->name('otp.send');
    Route::get('/verify-otp', [\App\Http\Controllers\Auth\GuestOtpController::class, 'verifyForm'])->name('otp.verify.form');
    Route::post('/verify-otp', [\App\Http\Controllers\Auth\GuestOtpController::class, 'verify'])->name('otp.verify');

    Route::middleware('auth')->group(function () {
        Route::get('/welcome', [\App\Http\Controllers\GuestPortalController::class, 'index'])->name('portal');
        Route::post('/welcome', [\App\Http\Controllers\OrderController::class, 'store'])->name('orders.store');
        Route::post('/logout', [\App\Http\Controllers\Auth\GuestOtpController::class, 'destroy'])->name('logout');

        Route::get('/guide', function () {
            $propertyId = request()->query('p', \App\Models\Property::first()?->id);
            $property = \App\Models\Property::with('amenities')->find($propertyId);

            if (! $property) {
                abort(404);
            }

            return Inertia::render('Guest/Guidebook', [
                'property' => $property,
                'amenities' => $property->amenities,
            ]);
        })->name('guidebook');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/two-factor/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('profile.two-factor.confirm');
    Route::post('/profile/two-factor/disable', [ProfileController::class, 'disableTwoFactor'])->name('profile.two-factor.disable');

    // Notification Preferences
    Route::get('/profile/notifications', [\App\Http\Controllers\NotificationPreferenceController::class, 'index'])->name('profile.notifications');
    Route::put('/profile/notifications', [\App\Http\Controllers\NotificationPreferenceController::class, 'update'])->name('profile.notifications.update');
});

require __DIR__.'/auth.php';

// M-Pesa Callback (public)
Route::post('/api/mpesa/callback', [\App\Http\Controllers\MpesaCallbackController::class, 'handle'])->name('mpesa.callback');
