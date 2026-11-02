<?php

namespace App\Rules;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * One mobile number per account. Numbers are compared in their normalised form (+919830012345),
 * so "98300 12345" and "+91 98300-12345" count as the same number. Deleted accounts don't count.
 */
class UniquePhone
{
    public const MESSAGE = 'This mobile number is already registered with another account. Please sign in, or use a different number.';

    public static function except(?int $userId = null): Unique
    {
        return Rule::unique('users', 'phone')->withoutTrashed()->ignore($userId);
    }

    /**
     * For a guest booking: the number may belong to the account with this same email (it is theirs),
     * but not to anyone else's account.
     */
    public static function exceptEmail(string $email): Unique
    {
        return Rule::unique('users', 'phone')
            ->withoutTrashed()
            ->where(fn ($query) => $query->where('email', '!=', strtolower(trim($email))));
    }
}
