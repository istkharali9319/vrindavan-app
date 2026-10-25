<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    /**
     * Two-factor authentication setup. Enabling, confirming and disabling
     * go through Fortify's two-factor routes.
     */
    public function edit(Request $request): View
    {
        return view('account.security', [
            'user' => $request->user(),
        ]);
    }
}
