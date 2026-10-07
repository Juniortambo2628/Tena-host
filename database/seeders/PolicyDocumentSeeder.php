<?php

namespace Database\Seeders;

use App\Models\PolicyDocument;
use Illuminate\Database\Seeder;

class PolicyDocumentSeeder extends Seeder
{
    /** Slug => policy type. Text lives in database/policies/tenafi.php. */
    private const TYPES = [
        'privacy-policy' => 'privacy_policy',
        'terms-of-service' => 'terms_of_service',
        'cookie-policy' => 'cookie_policy',
        'refund-policy' => 'refund_policy',
        'acceptable-use-policy' => 'acceptable_use',
        'data-processing-agreement' => 'data_processing',
    ];

    public function run(): void
    {
        $policies = require database_path('policies/tenafi.php');

        foreach (self::TYPES as $slug => $type) {
            PolicyDocument::firstOrCreate(['slug' => $slug], $policies[$slug] + [
                'type' => $type,
                'version' => '2.0',
                'is_published' => true,
                'effective_date' => now(),
                'last_reviewed_at' => now(),
                'last_reviewed_by' => 'System',
            ]);
        }
    }
}
