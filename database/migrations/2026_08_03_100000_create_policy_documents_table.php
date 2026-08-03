<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('description')->nullable();
            $table->longText('content');
            $table->enum('type', ['privacy_policy', 'terms_of_service', 'cookie_policy', 'refund_policy', 'acceptable_use', 'data_processing', 'other'])->default('other');
            $table->boolean('is_published')->default(false);
            $table->string('version')->default('1.0');
            $table->timestamp('effective_date')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->string('last_reviewed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_documents');
    }
};
