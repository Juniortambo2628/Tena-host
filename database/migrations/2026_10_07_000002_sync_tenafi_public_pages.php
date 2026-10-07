<?php

use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * TenaFi relaunch: create the /, /hosts, /business and site-wide CMS
 * sections from database/blueprints/public_pages.php, and rewrite the old
 * homepage copy (now /hosts) to the new direction. Admin-uploaded media is
 * preserved; see PageBlueprint for exactly what relaunch mode replaces.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::sync(relaunch: true);
    }

    public function down(): void
    {
        // Content-only migration; the schema rollback in
        // 2026_10_07_000001 removes the new pages' sections.
    }
};
