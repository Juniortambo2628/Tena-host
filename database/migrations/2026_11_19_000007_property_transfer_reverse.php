<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Property transfers, step 5 (docs/plans/property-transfer.md): for 30 days
 * an admin can reverse a completed transfer, once both owners agree in
 * writing. When and by whom is kept on the transfer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_transfers', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('property_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn('reversed_at');
        });
    }
};
