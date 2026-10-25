<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Deletes an account the privacy-friendly way: personal data is anonymised and the row is
 * soft-deleted, so bookings and payments that reference it stay intact for accounting.
 */
class DeleteUserAccount
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->syncRoles([]);

            $user->forceFill([
                'name' => 'Deleted user',
                'email' => "deleted-{$user->id}-".Str::lower(Str::random(8)).'@deleted.invalid',
                'phone' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();

            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->delete();
        });
    }
}
