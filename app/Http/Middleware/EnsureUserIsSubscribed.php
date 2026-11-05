<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Services\Billing\PlanPricing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->isAdmin()) {
            return $next($request);
        }

        if (! PlanPricing::enforced()) {
            return $next($request);
        }

        // A team member works under the owner's plan.
        $owner = $request->user()?->accountOwner();
        if ($owner && ! $owner->subscribed('default') && ! $owner->onTrial()) {
            // Pay per event: an organiser without a plan can still set up venues and events, and pay for each event.
            if (Event::billingEnabled() && $owner->isEventAccount() && $request->routeIs('host.events.*', 'host.properties.*', 'host.access-points.*')) {
                return $next($request);
            }

            return redirect()->route('host.billing.index');
        }

        return $next($request);
    }
}
