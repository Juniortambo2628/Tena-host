<?php

use App\Http\Controllers\AccessPointController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminTransferController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\DeviceAgreementController;
use App\Http\Controllers\Admin\EmailImageController;
use App\Http\Controllers\Admin\EventQuoteController;
use App\Http\Controllers\Admin\HostController;
use App\Http\Controllers\Admin\LandingController;
use App\Http\Controllers\Admin\MpesaSettingsController;
use App\Http\Controllers\Admin\NotificationTestController;
use App\Http\Controllers\Admin\PolicyDocumentController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UnifiSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\Auth\GuestOtpController;
use App\Http\Controllers\CallbackController;
use App\Http\Controllers\CampaignTrackingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventPaymentController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestPortalController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HostDashboardController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MessagingWebhookController;
use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyTransferController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\ReviewLinkController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\WifiPortalController;
use App\Http\Middleware\EnsureUserIsSubscribed;
use App\Models\LandingPage;
use App\Services\Cms\V2Pages;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public CMS pages: / here, /hosts and /business via the catch-all at the
// bottom of this file (so it never shadows an app route).
Route::get('/', [PublicPageController::class, 'show'])->name('home');
// The v2 public pages (the OKJT kit's design), previewed behind Admin -> Settings.
Route::get('/v2', [PublicPageController::class, 'v2'])->name('v2.home');
Route::get('/v2/{audience}', [PublicPageController::class, 'v2Audience'])
    ->whereIn('audience', array_keys(V2Pages::AUDIENCES))->name('v2.audience');

foreach (config('public_pages.policies') as $path => $slug) {
    Route::get("/{$path}", [PublicPageController::class, 'policy'])->defaults('slug', $slug)->name("public.{$path}");
}

foreach (config('public_pages.redirects') as $from => $to) {
    Route::permanentRedirect($from, $to);
}

Route::get('/sitemap.xml', function () {
    $paths = LandingPage::where('is_routable', true)->where('is_active', true)->orderBy('sort_order')->get()
        ->reject(fn (LandingPage $page) => $page->slug === 'pricing' && ! PublicPageController::pricingPageEnabled())
        ->map->path()
        ->merge(array_map(fn ($path) => "/{$path}", array_keys(config('public_pages.policies'))));

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($paths as $path) {
        $priority = $path === '/' ? '1.0' : '0.8';
        $xml .= '<url><loc>'.e(url($path)).'</loc><changefreq>weekly</changefreq><priority>'.$priority.'</priority></url>';
    }
    $xml .= '</urlset>';

    return response($xml, 200)
        ->header('Content-Type', 'application/xml')
        ->header('Cache-Control', 'public, max-age=86400');
})->name('sitemap');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/api/signups', [SignupController::class, 'store'])->name('signups.store');
Route::post('/api/callbacks', [CallbackController::class, 'store'])->middleware('throttle:10,10')->name('callbacks.store');
Route::post('/api/track', [TrackController::class, 'store'])->name('track');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Global Search
    Route::get('/search', [SearchController::class, 'search'])->name('search');

    // Admin Features
    Route::get('/hosts', [HostController::class, 'index'])->name('hosts.index');
    Route::get('/hosts/{user}', [HostController::class, 'show'])->name('hosts.show');
    Route::put('/hosts/{user}', [HostController::class, 'update'])->name('hosts.update');
    Route::delete('/hosts/{user}', [HostController::class, 'destroy'])->name('hosts.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/system', [SystemController::class, 'index'])->name('system.index');

    // Pay per event: organisers' quote requests and the prices set for them
    Route::get('/event-quotes', [EventQuoteController::class, 'index'])->name('event-quotes.index');
    Route::put('/event-quotes/{event}', [EventQuoteController::class, 'update'])->name('event-quotes.update');

    // Device register: TenaFi devices paid for monthly (docs/plans/device-monthly-payments.md)
    Route::get('/devices', [DeviceAgreementController::class, 'index'])->name('devices.index');
    Route::post('/devices', [DeviceAgreementController::class, 'store'])->name('devices.store');
    Route::post('/devices/{agreement}/instalments/{instalment}/pay', [DeviceAgreementController::class, 'pay'])->name('devices.pay');
    Route::put('/devices/{agreement}/status', [DeviceAgreementController::class, 'status'])->name('devices.status');

    // Property transfers: see every transfer; hold, release or cancel one during its cooling-off
    Route::get('/transfers', [AdminTransferController::class, 'index'])->name('transfers.index');
    Route::post('/transfers/{transfer}/hold', [AdminTransferController::class, 'hold'])->name('transfers.hold');
    Route::post('/transfers/{transfer}/release', [AdminTransferController::class, 'release'])->name('transfers.release');
    Route::post('/transfers/{transfer}/cancel', [AdminTransferController::class, 'cancel'])->name('transfers.cancel');

    // Payments
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/hosts', [AdminPaymentController::class, 'hosts'])->name('payments.hosts');
    Route::get('/payments/{transaction}', [AdminPaymentController::class, 'show'])->name('payments.show');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/branding/{key}', [BrandingController::class, 'upload'])->name('branding.upload');
    Route::delete('/branding/{key}', [BrandingController::class, 'reset'])->name('branding.reset');

    // WiFi captive portal (UniFi) settings
    Route::get('/wifi', [UnifiSettingsController::class, 'edit'])->name('wifi.edit');
    Route::post('/wifi', [UnifiSettingsController::class, 'update'])->name('wifi.update');
    Route::post('/wifi/test', [UnifiSettingsController::class, 'test'])->name('wifi.test');
    Route::post('/wifi/sync', [UnifiSettingsController::class, 'sync'])->name('wifi.sync');
    Route::get('/mpesa', [MpesaSettingsController::class, 'edit'])->name('mpesa.edit');
    Route::post('/mpesa', [MpesaSettingsController::class, 'update'])->name('mpesa.update');
    Route::post('/mpesa/payouts/{user}/settle', [MpesaSettingsController::class, 'settle'])->name('mpesa.settle');

    // Notification Test
    Route::post('/notifications/test', [NotificationTestController::class, 'send'])->name('notifications.test');

    // Email Image Upload
    Route::post('/email-images/upload', [EmailImageController::class, 'upload'])->name('email-images.upload');

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
    Route::post('/registrations/{registration}/convert', [RegistrationController::class, 'convert'])->name('registrations.convert');

    // Landing Page CMS
    Route::get('/landing', [LandingController::class, 'index'])->name('landing.index');
    Route::put('/landing/sections/{section}', [LandingController::class, 'updateSection'])->name('landing.sections.update');
    Route::post('/landing/sections/reorder', [LandingController::class, 'reorder'])->name('landing.sections.reorder');
    Route::post('/landing/content', [LandingController::class, 'storeContent'])->name('landing.content.store');
    Route::put('/landing/content', [LandingController::class, 'updateContent'])->name('landing.content.update');
    Route::delete('/landing/content/{content}', [LandingController::class, 'destroyContent'])->name('landing.content.destroy');
    Route::delete('/landing/sections/{section}/items/{array}/{index}', [LandingController::class, 'destroyItem'])->whereNumber('index')->where('array', '[a-z_]+')->name('landing.items.destroy');
    Route::post('/landing/sections/{section}/media', [LandingController::class, 'uploadMedia'])->name('landing.media.upload');
    Route::post('/landing/sections/{section}/media/assign', [LandingController::class, 'assignMedia'])->name('landing.media.assign');
    Route::get('/landing/media/all', [LandingController::class, 'listMedia'])->name('landing.media.list');
    Route::put('/landing/media/{media}/crop', [LandingController::class, 'updateCrop'])->name('landing.media.crop');
    Route::delete('/landing/media/{media}', [LandingController::class, 'destroyMedia'])->name('landing.media.destroy');
    Route::get('/landing/media/{media}/download', [LandingController::class, 'downloadMedia'])->name('landing.media.download');
});

// Host Routes
Route::middleware(['auth', 'verified', 'host'])->prefix('host')->name('host.')->group(function () {
    // Global Search
    Route::get('/search', [SearchController::class, 'search'])->name('search');

    // Billing & Payments (the account owner's; team members work under the owner's plan)
    Route::middleware('account.owner')->group(function () {
        Route::get('/billing', [SubscriptionController::class, 'index'])->name('billing.index');
        Route::post('/billing/paystack', [SubscriptionController::class, 'storePaystack'])->name('billing.paystack');
        Route::post('/billing/mpesa', [SubscriptionController::class, 'storeMpesa'])->name('billing.mpesa');
        Route::post('/billing/simulate', [SubscriptionController::class, 'simulateMpesa'])->name('billing.simulate');
    });

    // Property transfers: an owner hands a property to another TenaFi account (Admin -> Settings, starts off).
    // Outside the plan check, so a new owner without a plan yet can still accept.
    Route::middleware('account.owner')->group(function () {
        Route::get('/transfers', [PropertyTransferController::class, 'index'])->name('transfers.index');
        Route::post('/transfers', [PropertyTransferController::class, 'store'])->name('transfers.store');
        Route::post('/transfers/{transfer}/accept', [PropertyTransferController::class, 'accept'])->name('transfers.accept');
        Route::post('/transfers/{transfer}/decline', [PropertyTransferController::class, 'decline'])->name('transfers.decline');
        Route::post('/transfers/{transfer}/cancel', [PropertyTransferController::class, 'cancel'])->name('transfers.cancel');
    });

    // Protected Host Routes (Requires Subscription)
    Route::middleware([EnsureUserIsSubscribed::class])->group(function () {
        Route::get('/dashboard', [HostDashboardController::class, 'index'])->name('dashboard');

        // Properties
        Route::get('properties/export', [PropertyController::class, 'export'])->name('properties.export');
        Route::match(['get', 'post'], 'properties/{property}/splash-preview', [WifiPortalController::class, 'preview'])->name('properties.splash-preview');
        Route::get('properties/{property}/splash', [PropertyController::class, 'splash'])->name('properties.splash');
        Route::post('properties/{property}/splash', [PropertyController::class, 'updateSplash'])->name('properties.splash.update');
        Route::resource('properties', PropertyController::class)->except(['create']);
        // Review requests to everyone captured so far (the owner's, like marketing).
        Route::post('properties/{property}/review-requests', [PropertyController::class, 'askEveryone'])->middleware('account.owner')->name('properties.review-requests');

        // Access Points
        Route::get('/access-points', [AccessPointController::class, 'index'])->name('access-points.index');
        Route::post('/access-points', [AccessPointController::class, 'store'])->name('access-points.store');
        Route::get('/access-points/{accessPoint}', [AccessPointController::class, 'show'])->name('access-points.show');
        Route::match(['put', 'patch'], '/access-points/{accessPoint}', [AccessPointController::class, 'update'])->name('access-points.update');
        Route::delete('/access-points/{accessPoint}', [AccessPointController::class, 'destroy'])->name('access-points.destroy');

        // Amenities
        Route::get('/amenities', [AmenityController::class, 'index'])->name('amenities.index');
        Route::post('/amenities', [AmenityController::class, 'store'])->name('amenities.store');
        Route::get('/amenities/{amenity}', [AmenityController::class, 'show'])->name('amenities.show');
        Route::match(['put', 'patch'], '/amenities/{amenity}', [AmenityController::class, 'update'])->name('amenities.update');
        Route::delete('/amenities/{amenity}', [AmenityController::class, 'destroy'])->name('amenities.destroy');

        // Orders
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::match(['put', 'patch'], '/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        // Guests
        Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
        Route::post('/guests', [GuestController::class, 'store'])->name('guests.store');
        Route::get('/guests/{guest}', [GuestController::class, 'show'])->name('guests.show');
        Route::match(['put', 'patch'], '/guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');

        // Events (event accounts): a dated event at one of their venues, and who came.
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
        Route::match(['put', 'patch'], '/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
        // Pay per event: ask for a quote, then pay it (account owners pay).
        Route::post('/events/{event}/quote', [EventPaymentController::class, 'requestQuote'])->name('events.quote');
        Route::post('/events/{event}/pay/mpesa', [EventPaymentController::class, 'payMpesa'])->name('events.pay.mpesa');
        Route::post('/events/{event}/pay/paystack', [EventPaymentController::class, 'payPaystack'])->name('events.pay.paystack');

        // Reception visitor log (Admin -> Settings -> Visitor log): a day's visits, walk-ins and sign-outs.
        Route::get('/visitors', [VisitorController::class, 'index'])->name('visitors.index');
        Route::post('/visitors', [VisitorController::class, 'store'])->name('visitors.store');
        Route::patch('/visitors/{visit}/leave', [VisitorController::class, 'leave'])->name('visitors.leave');

        // Team: people the owner invites, each with their own login and sites (Admin -> Settings switches it on).
        Route::middleware('account.owner')->group(function () {
            Route::get('/team', [TeamController::class, 'index'])->name('team.index');
            Route::post('/team', [TeamController::class, 'store'])->name('team.store');
            Route::match(['put', 'patch'], '/team/{member}', [TeamController::class, 'update'])->name('team.update');
            Route::delete('/team/{member}', [TeamController::class, 'destroy'])->name('team.destroy');
        });

        // Marketing (the account owner's)
        Route::middleware('account.owner')->group(function () {
            Route::get('/marketing', [MarketingController::class, 'index'])->name('marketing.index');
            Route::get('/marketing/create', [MarketingController::class, 'create'])->name('marketing.builder');
            Route::get('/marketing/estimate', [MarketingController::class, 'estimate'])->name('marketing.estimate');
            Route::post('/marketing/preview', [MarketingController::class, 'preview'])->name('marketing.preview');
            Route::post('/marketing', [MarketingController::class, 'store'])->name('marketing.store');
            Route::get('/marketing/{id}/edit', [MarketingController::class, 'edit'])->name('marketing.edit');
            Route::match(['put', 'patch'], '/marketing/{id}', [MarketingController::class, 'update'])->name('marketing.update');
            Route::delete('/marketing/{id}', [MarketingController::class, 'destroy'])->name('marketing.destroy');
            Route::get('/marketing/{id}/analytics', [MarketingController::class, 'analytics'])->name('marketing.analytics');
            Route::post('/marketing/{id}/activate', [MarketingController::class, 'activate'])->name('marketing.activate');
            Route::post('/marketing/{id}/pause', [MarketingController::class, 'pause'])->name('marketing.pause');
        });
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
            $property = GuestPortalController::accessibleProperty(request(), ['amenities']);

            return Inertia::render('Guest/Guidebook', [
                'property' => $property,
                'amenities' => $property->amenities,
            ]);
        })->name('guidebook');
    });
});

Route::middleware('auth')->group(function () {
    // Help Center: how-to guides for every feature and how to reach support.
    Route::get('/help', [HelpController::class, 'index'])->middleware('verified')->name('help');
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
Route::post('/api/mpesa/extras/callback', [MpesaCallbackController::class, 'extras'])->name('mpesa.extras.callback');

// An event's sponsor report, opened from a signed link the organiser shares (no login).
Route::get('/events/{event}/sponsor-report', [EventController::class, 'sponsorReport'])->middleware('signed')->name('events.sponsor-report');

// Try-it-yourself demo of the WiFi page (homepage QR code). Saves nothing.
Route::get('/demo', [DemoController::class, 'show'])->name('demo');
Route::post('/demo', [DemoController::class, 'connect'])->middleware('throttle:30,1')->name('demo.connect');

// Ubiquiti UniFi external captive portal (public — pre-auth guest devices).
// Point UniFi "Guest Control -> External Portal Server" at the /portal URL.
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [WifiPortalController::class, 'show'])->name('show');
    Route::post('/connect', [WifiPortalController::class, 'connect'])->name('connect');
    // An event sponsor's link: counted for their report, then redirected.
    Route::get('/sponsor/{event}', [WifiPortalController::class, 'sponsor'])->middleware('throttle:60,1')->name('sponsor');
});

// UniFi controllers that append their own path (http://<portal>/guest/s/<site>/?id=...)
// land here; forward to /portal with the UniFi params intact.
Route::get('/guest/s/{site}', [WifiPortalController::class, 'unifiPath'])
    ->where('site', '[A-Za-z0-9_-]+')
    ->name('portal.unifi-path');

// Inbound messaging: STOP/START replies and WhatsApp read receipts.
Route::get('/api/whatsapp/webhook', [MessagingWebhookController::class, 'verifyWhatsApp'])->name('whatsapp.webhook.verify');
Route::post('/api/whatsapp/webhook', [MessagingWebhookController::class, 'whatsApp'])->name('whatsapp.webhook');
Route::post('/api/sms/inbound', [MessagingWebhookController::class, 'sms'])->name('sms.inbound');

// Campaign link and open tracking (CampaignLinks).
// The email link opens "Unsubscribe?" (GET); pressing the button unsubscribes (POST), so link scanners can't.
Route::get('/c/{token}/unsubscribe', [CampaignTrackingController::class, 'confirmUnsubscribe'])->where('token', '[A-Za-z0-9]{8}')->middleware('signed')->name('campaigns.unsubscribe');
Route::post('/c/{token}/unsubscribe', [CampaignTrackingController::class, 'unsubscribe'])->where('token', '[A-Za-z0-9]{8}')->middleware('signed');
Route::get('/c/{token}/o.gif', [CampaignTrackingController::class, 'open'])->where('token', '[A-Za-z0-9]{8}')->name('campaigns.open');
Route::get('/c/{token}/{n}', [CampaignTrackingController::class, 'click'])->where(['token' => '[A-Za-z0-9]{8}', 'n' => '[0-9]{1,2}'])->name('campaigns.click');

// Business customer homepage (menu, offers, events).
Route::get('/places/{property}', [VenueController::class, 'show'])->whereNumber('property')->name('venue.show');

// Short review link sent to guests (counts the click, then redirects).
Route::get('/r/{token}', ReviewLinkController::class)->where('token', '[A-Za-z0-9]{8}')->name('reviews.go');
Route::get('/r/{token}/unsubscribe', [ReviewLinkController::class, 'confirmUnsubscribe'])->where('token', '[A-Za-z0-9]{8}')->middleware('signed')->name('reviews.unsubscribe');
Route::post('/r/{token}/unsubscribe', [ReviewLinkController::class, 'unsubscribe'])->where('token', '[A-Za-z0-9]{8}')->middleware('signed');

// A TenaFi device's own page (/pricing/pro): off with /pricing (Admin -> Settings).
Route::get('/pricing/{device}', [PublicPageController::class, 'device'])
    ->where('device', '[a-z0-9-]+')
    ->name('pricing.device');

// "It wasn't me": the owner cancels a property transfer from their email, without signing in (signed link).
Route::match(['get', 'post'], '/transfers/{transfer}/cancel', [PropertyTransferController::class, 'cancelLink'])
    ->whereNumber('transfer')->middleware(['signed', 'throttle:20,1'])->name('transfers.cancel-link');

// Catch-all for CMS pages (/hosts, /business, ...). Must stay last.
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('public.page');
