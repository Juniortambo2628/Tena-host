<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\Pms\PmsSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PmsWebhookController extends Controller
{
    public function __construct(
        protected PmsSyncService $syncService,
    ) {
        //
    }

    public function handle(Request $request, string $provider)
    {
        $apiKey = config('services.pms.webhook_secret');

        if ($apiKey && $request->header('X-PMS-Signature') !== $apiKey) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $propertyId = $request->input('property_id');
        $property = $propertyId ? Property::find($propertyId) : null;

        if (! $property) {
            return response()->json(['message' => 'Property not found.'], 404);
        }

        if ($property->pms_integration_type && strtolower($property->pms_integration_type) !== $provider) {
            return response()->json(['message' => 'Provider mismatch.'], 400);
        }

        try {
            $result = $this->syncService->sync($property, $provider, $request->all());

            Log::info("PMS sync completed for property {$property->id}", $result);

            return response()->json([
                'message' => 'Sync completed.',
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error("PMS sync failed for property {$property->id}", ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Sync failed.', 'error' => $e->getMessage()], 500);
        }
    }
}
