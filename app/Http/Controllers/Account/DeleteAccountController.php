<?php

namespace App\Http\Controllers\Account;

use App\Actions\Account\DeleteUserAccount;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeleteAccountController extends Controller
{
    public function __invoke(Request $request, DeleteUserAccount $deleteUserAccount): RedirectResponse
    {
        $request->validateWithBag('deleteAccount', [
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if ($user->isStaff()) {
            return back()->withErrors(
                ['password' => 'Staff accounts can only be removed by a super admin.'],
                'deleteAccount',
            );
        }

        Auth::guard('web')->logout();

        $deleteUserAccount->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'account-deleted');
    }
}
