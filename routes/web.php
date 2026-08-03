<?php

use App\Http\Controllers\AccessPointController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\HostController;
use App\Http\Controllers\Admin\LandingController;
use App\Http\Controllers\Admin\NotificationTestController;
use App\Http\Controllers\Admin\PolicyDocumentController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\Auth\GuestOtpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestPortalController;
use App\Http\Controllers\HostDashboardController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Middleware\EnsureUserIsSubscribed;
use App\Models\Property;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'landingContent' => LandingController::getPublicData(),
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Admin Features
    Route::get('/hosts', [HostController::class, 'index'])->name('hosts.index');
    Route::get('/hosts/{user}', [HostController::class, 'show'])->name('hosts.show');
    Route::put('/hosts/{user}', [HostController::class, 'update'])->name('hosts.update');
    Route::delete('/hosts/{user}', [HostController::class, 'destroy'])->name('hosts.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/system', [SystemController::class, 'index'])->name('system.index');

    // Payments
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/hosts', [AdminPaymentController::class, 'hosts'])->name('payments.hosts');
    Route::get('/payments/{transaction}', [AdminPaymentController::class, 'show'])->name('payments.show');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Notification Test
    Route::post('/notifications/test', [NotificationTestController::class, 'send'])->name('notifications.test');

    // Policy Documents
    Route::get('/policies', [PolicyDocumentController::class, 'index'])->name('policies.index');
    Route::get('/policies/create', [PolicyDocumentController::class, 'create'])->name('policies.create');
    Route::post('/policies', [PolicyDocumentController::class, 'store'])->name('policies.store');
    Route::get('/policies/{policy}', [PolicyDocumentController::class, 'show'])->name('policies.show');
    Route::get('/policies/{policy}/edit', [PolicyDocumentController::class, 'edit'])->name('policies.edit');
    Route::put('/policies/{policy}', [PolicyDocumentController::class, 'update'])->name('policies.update');
    Route::delete('/policies/{policy}', [PolicyDocumentController::class, 'destroy'])->name('policies.destroy');
    Route::post('/policies/{policy}/toggle', [PolicyDocumentController::class, 'togglePublish'])->name('policies.toggle');

    // Registrations
    Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::put('/registrations/{registration}', [RegistrationController::class, 'update'])->name('registrations.update');
    Route::delete('/registrations/{registration}', [RegistrationController::class, 'destroy'])->name('registrations.destroy');

    // Landing Page CMS
    Route::get('/landing', [LandingController::class, 'index'])->name('landing.index');
    Route::put('/landing/sections/{section}', [LandingController::class, 'updateSection'])->name('landing.sections.update');
    Route::post('/landing/sections/reorder', [LandingController::class, 'reorder'])->name('landing.sections.reorder');
    Route::post('/landing/content', [LandingController::class, 'storeContent'])->name('landing.content.store');
    Route::put('/landing/content', [LandingController::class, 'updateContent'])->name('landing.content.update');
    Route::delete('/landing/content/{content}', [LandingController::class, 'destroyContent'])->name('landing.content.destroy');
    Route::post('/landing/sections/{section}/media', [LandingController::class, 'uploadMedia'])->name('landing.media.upload');
    Route::post('/landing/sections/{section}/media/assign', [LandingController::class, 'assignMedia'])->name('landing.media.assign');
    Route::get('/landing/media/all', [LandingController::class, 'listMedia'])->name('landing.media.list');
    Route::put('/landing/media/{media}/crop', [LandingController::class, 'updateCrop'])->name('landing.media.crop');
    Route::delete('/landing/media/{media}', [LandingController::class, 'destroyMedia'])->name('landing.media.destroy');
    Route::get('/landing/media/{media}/download', [LandingController::class, 'downloadMedia'])->name('landing.media.download');
});

// Host Routes
Route::middleware(['auth', 'verified', 'host'])->prefix('host')->name('host.')->group(function () {
    // Billing & Payments (Accessible to all authenticated hosts)
    Route::get('/billing', [SubscriptionController::class, 'index'])->name('billing.index');
    Route::post('/billing/stripe', [SubscriptionController::class, 'storeStripe'])->name('billing.stripe');
    Route::post('/billing/mpesa', [SubscriptionController::class, 'storeMpesa'])->name('billing.mpesa');
    Route::post('/billing/simulate', [SubscriptionController::class, 'simulateMpesa'])->name('billing.simulate');

    // Protected Host Routes (Requires Subscription)
    Route::middleware([EnsureUserIsSubscribed::class])->group(function () {
        Route::get('/dashboard', [HostDashboardController::class, 'index'])->name('dashboard');

        // Properties
        Route::resource('properties', PropertyController::class);

        // Access Points
        Route::get('/access-points', [AccessPointController::class, 'index'])->name('access-points.index');
        Route::post('/access-points', [AccessPointController::class, 'store'])->name('access-points.store');
        Route::get('/access-points/{accessPoint}', [AccessPointController::class, 'show'])->name('access-points.show');
        Route::put('/access-points/{accessPoint}', [AccessPointController::class, 'update'])->name('access-points.update');
        Route::delete('/access-points/{accessPoint}', [AccessPointController::class, 'destroy'])->name('access-points.destroy');

        // Amenities
        Route::get('/amenities', [AmenityController::class, 'index'])->name('amenities.index');
        Route::post('/amenities', [AmenityController::class, 'store'])->name('amenities.store');
        Route::get('/amenities/{amenity}', [AmenityController::class, 'show'])->name('amenities.show');
        Route::put('/amenities/{amenity}', [AmenityController::class, 'update'])->name('amenities.update');
        Route::delete('/amenities/{amenity}', [AmenityController::class, 'destroy'])->name('amenities.destroy');

        // Orders
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        // Guests
        Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
        Route::post('/guests', [GuestController::class, 'store'])->name('guests.store');
        Route::get('/guests/{guest}', [GuestController::class, 'show'])->name('guests.show');
        Route::put('/guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');

        // Marketing
        Route::get('/marketing', [MarketingController::class, 'index'])->name('marketing.index');
        Route::get('/marketing/create', [MarketingController::class, 'create'])->name('marketing.builder');
        Route::post('/marketing', [MarketingController::class, 'store'])->name('marketing.store');
        Route::get('/marketing/{id}/edit', [MarketingController::class, 'edit'])->name('marketing.edit');
        Route::put('/marketing/{id}', [MarketingController::class, 'update'])->name('marketing.update');
        Route::delete('/marketing/{id}', [MarketingController::class, 'destroy'])->name('marketing.destroy');
        Route::get('/marketing/{id}/analytics', [MarketingController::class, 'analytics'])->name('marketing.analytics');
        Route::post('/marketing/{id}/activate', [MarketingController::class, 'activate'])->name('marketing.activate');
        Route::post('/marketing/{id}/pause', [MarketingController::class, 'pause'])->name('marketing.pause');
    });
});

// Staff Routes
Route::middleware(['auth', 'verified', 'staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
});
Route::prefix('g')->name('guest.')->group(function () {
    Route::get('/login', [GuestOtpController::class, 'create'])->name('login');
    Route::post('/login', [GuestOtpController::class, 'store'])->name('otp.send');
    Route::get('/verify-otp', [GuestOtpController::class, 'verifyForm'])->name('otp.verify.form');
    Route::post('/verify-otp', [GuestOtpController::class, 'verify'])->name('otp.verify');

    Route::middleware('auth')->group(function () {
        Route::get('/welcome', [GuestPortalController::class, 'index'])->name('portal');
        Route::post('/welcome', [OrderController::class, 'store'])->name('orders.store');
        Route::post('/logout', [GuestOtpController::class, 'destroy'])->name('logout');

        Route::get('/guide', function () {
            $propertyId = request()->query('p', Property::first()?->id);
            $property = Property::with('amenities')->find($propertyId);

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
    Route::get('/profile/notifications', [NotificationPreferenceController::class, 'index'])->name('profile.notifications');
    Route::put('/profile/notifications', [NotificationPreferenceController::class, 'update'])->name('profile.notifications.update');
});

require __DIR__.'/auth.php';

// M-Pesa Callback (public)
Route::post('/api/mpesa/callback', [MpesaCallbackController::class, 'handle'])->name('mpesa.callback');
