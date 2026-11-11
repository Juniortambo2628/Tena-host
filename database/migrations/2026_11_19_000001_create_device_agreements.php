<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The device register (docs/plans/device-monthly-payments.md, step 1): each
 * TenaFi device paid for monthly, its instalments, and a log of everything
 * done to it. Recorded by hand in Admin -> Devices for now; reminders,
 * automatic M-Pesa collection and pausing come later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the client (account owner)
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('access_point_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_type', 30); // lite, pro, outdoor (the /pricing device ids)
            $table->string('serial_number', 80)->nullable();
            $table->string('mac_address', 32)->nullable();
            $table->unsignedInteger('price_kes'); // the device's upfront price, for the record
            $table->unsignedInteger('monthly_kes'); // the device add-on each month
            $table->unsignedTinyInteger('months');
            $table->date('starts_on'); // installation day; the first instalment is due then
            $table->string('status', 30)->default('active');
            $table->timestamp('paid_off_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('serial_number');
        });

        Schema::create('device_instalments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_agreement_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->date('due_on');
            $table->unsignedInteger('amount_kes');
            $table->unsignedInteger('paid_kes')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('reference', 60)->nullable(); // M-Pesa code or other receipt
            $table->string('status', 20)->default('due'); // due, partly_paid, paid, waived
            $table->timestamps();
            $table->unique(['device_agreement_id', 'number']);
            $table->index(['status', 'due_on']);
        });

        Schema::create('device_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who did it
            $table->string('type', 40);
            $table->string('note', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_events');
        Schema::dropIfExists('device_instalments');
        Schema::dropIfExists('device_agreements');
    }
};
