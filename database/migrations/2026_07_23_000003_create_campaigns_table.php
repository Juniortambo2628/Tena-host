<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['email', 'sms'])->default('email');
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->string('trigger_event')->nullable();
            $table->string('trigger_delay')->nullable();
            $table->string('target_audience')->nullable();
            $table->unsignedInteger('total_sent')->default(0);
            $table->unsignedInteger('total_opened')->default(0);
            $table->unsignedInteger('total_clicked')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
