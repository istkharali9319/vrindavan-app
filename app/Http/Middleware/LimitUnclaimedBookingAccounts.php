<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guests are signed in to the account made from their booking straight away, before they have proved
 * the email address is theirs. Until they set a password from the emailed link, they can see their
 * stays, invoices and notifications, but not change the profile, password, two-factor settings or
 * delete the account.
 */
class LimitUnclaimedBookingAccounts
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isUnclaimedBookingAccount()
            && ($request->is('user/*') || $request->routeIs('account.profile', 'account.security', 'account.destroy'))) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Set your password first.'], 403)
                : redirect()->route('account.dashboard')->with('status', 'Please set your password first — use the link we emailed you.');
        }

        return $next($request);
    }
}
