<?php

use App\Services\Cms\PageBlueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * The homepage "Try it yourself" section (type demo), added hidden. It also
 * stays hidden until Admin -> Settings switches the live demo on.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageBlueprint::addSections('home', ['demo' => 'detailed_features__home'], active: false);
    }

    public function down(): void
    {
        //
    }
};
