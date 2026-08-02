<?php

namespace App\Http\Middleware;

use App\Models\Setting;
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

        $billingEnabled = Setting::getValue('billing_enabled', 'auto');

        // If billing is explicitly disabled, skip subscription checks.
        if ($billingEnabled === 'disabled') {
            return $next($request);
        }

        // In 'auto' mode, skip if no Stripe key is configured.
        if ($billingEnabled === 'auto' && ! config('services.stripe.key')) {
            return $next($request);
        }

        if ($request->user() && ! $request->user()->subscribed('default') && ! $request->user()->onTrial()) {
            return redirect()->route('host.billing.index');
        }

        return $next($request);
    }
}
