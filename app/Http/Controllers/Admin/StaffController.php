<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff.index', [
            'staff' => User::role(Role::staffValues())->with('roles')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create', [
            'roles' => Role::staff(),
        ]);
    }

    /**
     * Creates the account with an unusable random password and emails a link to set their own.
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => Hash::make(Str::random(64)),
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->assignRole($request->validated('role'));

            return $user;
        });

        Password::broker()->sendResetLink(['email' => $user->email]);

        return redirect()->route('admin.staff.index')
            ->with('status', "{$user->name} was added. We emailed them a link to set their password.");
    }

    public function edit(User $user): View
    {
        abort_unless($user->isStaff(), 404);

        return view('admin.staff.edit', [
            'member' => $user->load('roles'),
            'roles' => Role::staff(),
        ]);
    }

    public function update(UpdateStaffRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        DB::transaction(function () use ($request, $user): void {
            $user->update([
                'name' => $request->validated('name'),
                'phone' => $request->validated('phone'),
            ]);

            $user->syncRoles([$request->validated('role')]);
        });

        return redirect()->route('admin.staff.index')->with('status', "{$user->name} was updated.");
    }
}
