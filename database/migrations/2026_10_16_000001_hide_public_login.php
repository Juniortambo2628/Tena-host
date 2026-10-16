<?php

use App\Models\LandingContent;
use App\Models\LandingPage;
use Illuminate\Database\Migrations\Migration;

/**
 * TenaFi is fully managed, so customers get no dashboard at launch. The
 * public header loses its Login button (staff still sign in at /login) and
 * its CTA becomes "Book Now" unless an admin already renamed it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $header = fn ($q) => $q->where('section_key', 'header');

        LandingContent::whereHas('section', $header)
            ->whereIn('content_key', ['login_label', 'login_url'])
            ->delete();

        LandingContent::whereHas('section', $header)
            ->where('content_key', 'join_label')
            ->whereIn('value', ['Join', 'join', 'JOIN'])
            ->update(['value' => 'Book Now']);

        LandingPage::clearCache();
    }

    public function down(): void {}
};
