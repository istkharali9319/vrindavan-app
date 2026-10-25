<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Like "verified", but also lets in guests signed in to the account made from their booking,
 * so they land on My stays right after booking.
 */
class EnsureVerifiedOrBookingAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isUnclaimedBookingAccount()) {
            return $next($request);
        }

        return app(EnsureEmailIsVerified::class)->handle($request, $next);
    }
}
