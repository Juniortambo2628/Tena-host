<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Properties Table
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('wifi_ssid')->nullable();
            $table->integer('occupancy_threshold')->default(20);
            $table->string('logo_path')->nullable();
            $table->string('splash_image_path')->nullable();
            $table->json('branding_json')->nullable();
            $table->string('pms_integration_type')->nullable(); // e.g., 'Beds24', 'Guesty'
            $table->string('pms_connection_status')->default('disconnected');
            $table->timestamps();
        });

        // Access Points Table
        Schema::create('access_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('mac_address')->unique();
            $table->string('name');
            $table->enum('status', ['online', 'offline'])->default('offline');
            $table->timestamp('last_seen')->nullable();
            $table->integer('connected_clients_count')->default(0);
            $table->timestamps();
        });

        // Guests Table
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->timestamp('last_connected')->nullable();
            $table->integer('total_visits')->default(1);
            $table->string('source')->default('WiFi'); // WiFi, Manual, PMS
            $table->timestamps();

            $table->unique(['property_id', 'email']);
        });

        // Amenities Table
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('amenity_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'fulfilled', 'cancelled'])->default('pending');
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('guests');
        Schema::dropIfExists('access_points');
        Schema::dropIfExists('properties');
    }
};
