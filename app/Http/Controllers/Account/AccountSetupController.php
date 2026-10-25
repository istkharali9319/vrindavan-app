<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountSetupNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

/**
 * The "Set my password" link from AccountSetupNotification. It opens the normal reset-password
 * form with a fresh token, so the guest can choose a password for the account made from their booking.
 */
class AccountSetupController extends Controller
{
    public function show(User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail() || $user->isStaff()) {
            return redirect()->route('login')->with('status', 'Your account is already set up. Please sign in.');
        }

        Auth::logout();

        $token = Password::broker()->createToken($user);

        return redirect()->route('password.reset', ['token' => $token, 'email' => $user->email, 'setup' => 1]);
    }

    /**
     * Sends the "set your password" email again (from the banner in the account).
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isUnclaimedBookingAccount() && ($booking = $user->bookings()->latest('id')->first())) {
            $user->notify(new AccountSetupNotification($booking));
        }

        return back()->with('status', "We've emailed a new link to {$user->email}. It works for ".AccountSetupNotification::LINK_DAYS.' days.');
    }
}
