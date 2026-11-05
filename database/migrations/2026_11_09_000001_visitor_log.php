<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reception visitor log (company sites): the WiFi sign-in also asks who the
 * visitor is here to see, and reception keeps a dated log of visits (with
 * walk-ins added by hand and a sign-out time).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('visitor_log')->default(false)->after('staff_email_domain');
        });

        Schema::create('visitor_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('contact', 150)->nullable();
            $table->string('visiting', 100);
            $table->timestamp('arrived_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            $table->index(['property_id', 'arrived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_visits');
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('visitor_log');
        });
    }
};
