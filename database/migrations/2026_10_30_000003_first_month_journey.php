<?php

use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * "What you get in your first month" (journey section) directly above
 * pricing on /hosts and /business. Only the new sections are created; the
 * rest of each page, and its copy, stay as the admin left them.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::addSections('hosts', ['journey' => 'pricing']);
        PageBlueprint::addSections('business', ['journey' => 'pricing']);
    }

    public function down(): void
    {
        //
    }
};
