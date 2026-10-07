<?php

namespace App\Http\Middleware;

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

        if ($request->user() && ! $request->user()->subscribed('default') && ! $request->user()->onTrial()) {
            return redirect()->route('host.billing.index');
        }

        return $next($request);
    }
}
