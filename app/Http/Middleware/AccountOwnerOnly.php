<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Billing, marketing and the team are the account owner's: a team member
 * (a manager the owner invited) gets a 403.
 */
class AccountOwnerOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isTeamMember()) {
            abort(403, 'Only the account owner can do this.');
        }

        return $next($request);
    }
}
