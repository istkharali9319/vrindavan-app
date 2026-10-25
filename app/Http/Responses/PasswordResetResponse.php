<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;

/**
 * After choosing a new password, staff go to the staff sign-in page and guests to the website sign-in.
 */
class PasswordResetResponse implements PasswordResetResponseContract
{
    public function __construct(protected string $status) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($this->status)], 200);
        }

        $isStaff = User::firstWhere('email', strtolower((string) $request->input('email')))?->isStaff() ?? false;

        return redirect()->route($isStaff ? 'admin.login' : 'login')->with('status', trans($this->status));
    }
}
