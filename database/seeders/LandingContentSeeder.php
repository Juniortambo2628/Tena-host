<?php

namespace Database\Seeders;

use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Seeder;

class LandingContentSeeder extends Seeder
{
    /**
     * Fill in any missing public-page sections from the blueprint.
     * Additive only, so re-running never overwrites CMS edits.
     */
    public function run(): void
    {
        PageBlueprint::sync();
    }
}
