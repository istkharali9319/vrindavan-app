<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the customer area (/account) for guests' own accounts: staff are sent to the admin instead.
 */
class RedirectStaffToAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
