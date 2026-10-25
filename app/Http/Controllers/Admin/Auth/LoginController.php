<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Staff sign-in page. The form posts to Fortify's login route with portal=admin,
     * so rate limiting and two-factor challenges are shared with the guest login.
     */
    public function __invoke(): View
    {
        return view('admin.auth.login');
    }
}
