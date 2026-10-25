<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:create {email : Email address of the super admin} {--name= : Full name} {--phone= : Mobile number}')]
#[Description('Create a super admin account (asks for the password interactively)')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        $data = [
            'email' => $email,
            'name' => $this->option('name') ?: text('Full name', required: true),
            'phone' => PhoneNumber::normalize($this->option('phone')),
            'password' => password('Password (min. 8 characters, letters and numbers)', required: true),
        ];
        $data['password_confirmation'] = password('Confirm password', required: true);

        $validator = Validator::make($data, [
            'email' => ['required', 'email', 'max:191', Rule::unique(User::class)],
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole(Role::SuperAdmin->value);

            return $user;
        });

        $this->components->info("Super admin {$user->email} created.");
        $this->components->bulletList([
            'Sign in at '.route('admin.login'),
        ]);

        return self::SUCCESS;
    }
}
