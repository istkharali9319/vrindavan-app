<?php

namespace App\Actions\Fortify;

use App\Enums\Role;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Sign-ups allowed per IP address per hour.
     */
    public const REGISTRATIONS_PER_HOUR = 5;

    /**
     * Validate and create a newly registered customer.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $this->ensureNotRateLimited();

        $input['phone'] = PhoneNumber::normalize($input['phone'] ?? null) ?? ($input['phone'] ?? null);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:191',
                Rule::unique(User::class),
            ],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'password' => $this->passwordRules(),
        ], [
            'phone.regex' => 'Please enter a valid mobile number, e.g. 94320 64521 or +44 20 7946 0958.',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'password' => Hash::make($input['password']),
            ]);

            $user->assignRole(Role::Customer->value);

            return $user;
        });
    }

    /**
     * @throws ValidationException
     */
    protected function ensureNotRateLimited(): void
    {
        $key = 'register:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::REGISTRATIONS_PER_HOUR)) {
            throw ValidationException::withMessages([
                'email' => 'Too many accounts have been created from this network. Please try again later.',
            ]);
        }

        RateLimiter::hit($key, 3600);
    }
}
