<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replace the seeded "Tena Host" policies with the TenaFi versions
 * (database/policies/tenafi.php). Documents an admin has already rewritten
 * (reviewed by someone other than "System", no old brand) are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (require database_path('policies/tenafi.php') as $slug => $policy) {
            DB::table('policy_documents')
                ->where('slug', $slug)
                // Untouched seed (reviewed only by "System") or still the old brand.
                ->where(fn ($q) => $q->where('last_reviewed_by', 'System')
                    ->orWhere('content', 'like', '%Tena Host%')->orWhere('content', 'like', '%tena.host%'))
                ->update($policy + [
                    'version' => '2.0',
                    'effective_date' => now(),
                    'last_reviewed_at' => now(),
                    'last_reviewed_by' => 'TenaFi relaunch',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        //
    }
};
