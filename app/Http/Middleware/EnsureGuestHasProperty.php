<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestHasProperty
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isGuest()) {
            abort(403, 'Unauthorized.');
        }

        $propertyIds = $user->guestRecords()->pluck('property_id')->unique()->values();

        if ($propertyIds->isEmpty()) {
            abort(403, 'No property access assigned.');
        }

        $requestedPropertyId = $request->query('p');

        if ($requestedPropertyId && ! $propertyIds->contains($requestedPropertyId)) {
            abort(403, 'You do not have access to this property.');
        }

        // If no property requested, default to the first accessible one.
        if (! $requestedPropertyId) {
            $request->merge(['p' => $propertyIds->first()]);
        }

        return $next($request);
    }
}
