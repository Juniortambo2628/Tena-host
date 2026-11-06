<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The dashboard Help Center: how-to guides for every feature (the guides
 * themselves live in resources/js/lib/helpArticles.js) and how to reach
 * TenaFi support.
 */
class HelpController extends Controller
{
    public function index(): Response
    {
        $footer = LandingPage::siteSections()['footer']['content'] ?? [];

        return Inertia::render('Profile/Help', [
            'support' => [
                'email' => Setting::getValue('support_email', config('mail.from.address', 'info@tena.host')),
                'whatsapp' => trim(strip_tags((string) ($footer['whatsapp'] ?? ''))) ?: null,
            ],
        ]);
    }
}
