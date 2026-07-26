<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique(); // e.g. 'hero', 'features', 'how_it_works'
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('badge')->nullable();
            $table->string('bg')->default('white'); // white, gray, dark
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('landing_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('landing_sections')->cascadeOnDelete();
            $table->string('content_key'); // e.g. 'hero.title', 'hero.subtitle'
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, html, json
            $table->timestamps();

            $table->unique(['section_id', 'content_key']);
        });

        Schema::create('landing_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('landing_sections')->cascadeOnDelete();
            $table->string('media_key'); // e.g. 'hero.image', 'feature.1.image'
            $table->string('original_path'); // storage path
            $table->string('optimized_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration')->nullable(); // video duration in seconds
            $table->integer('sort_order')->default(0);
            $table->json('crop_data')->nullable(); // {x, y, width, height} for crop/reposition
            $table->timestamps();

            $table->unique(['section_id', 'media_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_media');
        Schema::dropIfExists('landing_content');
        Schema::dropIfExists('landing_sections');
    }
};
