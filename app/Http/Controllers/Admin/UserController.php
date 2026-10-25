<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Columns the list can be sorted by (query value => database column).
     *
     * @var array<string, string>
     */
    public const SORTABLE = [
        'name' => 'name',
        'email' => 'email',
        'joined' => 'created_at',
        'last_login' => 'last_login_at',
    ];

    /**
     * @var list<int>
     */
    public const PAGE_SIZES = [10, 25, 50, 100];

    /**
     * Server-side search, filtering, sorting and pagination of all accounts.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['customer', 'staff'])],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'deleted'])],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PAGE_SIZES)],
        ]);

        $sort = $filters['sort'] ?? 'joined';
        $direction = $filters['direction'] ?? ($sort === 'name' || $sort === 'email' ? 'asc' : 'desc');
        $perPage = (int) ($filters['per_page'] ?? 25);

        $users = User::query()
            ->with('roles')
            ->when(trim($filters['search'] ?? '') !== '', function ($query) use ($filters) {
                $search = trim($filters['search']);
                // "!" escapes the LIKE wildcards; it behaves the same on MySQL and SQLite.
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
                $digits = preg_replace('/\D/', '', $search);

                $query->where(function ($query) use ($like, $digits) {
                    $query->whereRaw("name LIKE ? ESCAPE '!'", [$like])
                        ->orWhereRaw("email LIKE ? ESCAPE '!'", [$like])
                        ->when(strlen($digits) >= 4, fn ($query) => $query->orWhere('phone', 'like', "%{$digits}%"));
                });
            })
            ->when(($filters['type'] ?? null) === 'customer', fn ($query) => $query->role(Role::Customer->value))
            ->when(($filters['type'] ?? null) === 'staff', fn ($query) => $query->role(Role::staffValues()))
            ->when(($filters['status'] ?? null) === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when(in_array($filters['status'] ?? null, ['active', 'suspended'], true),
                fn ($query) => $query->where('status', $filters['status']))
            ->orderBy(self::SORTABLE[$sort], $direction)
            ->orderBy('id', $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'counts' => [
                'all' => User::count(),
                'customers' => User::role(Role::Customer->value)->count(),
                'staff' => User::role(Role::staffValues())->count(),
                'suspended' => User::where('status', UserStatus::Suspended)->count(),
            ],
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
        ]);
    }

    public function show(User $user): View
    {
        $confirmed = $user->bookings()->where('status', BookingStatus::Confirmed);

        return view('admin.users.show', [
            'user' => $user->load('roles'),
            'stats' => [
                'bookings' => $user->bookings()->count(),
                'upcoming' => (clone $confirmed)->where('check_out', '>', now(config('booking.timezone'))->toDateString())->count(),
                'nights' => (int) (clone $confirmed)->sum('nights'),
                'spent' => (int) (clone $confirmed)->sum('total_amount'),
            ],
            'bookings' => $user->bookings()->with(['roomType', 'room'])->latest()->limit(20)->get(),
        ]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanChangeStatus($request->user(), $user);

        $user->forceFill(['status' => UserStatus::Suspended])->save();

        return back()->with('status', "{$user->name} has been suspended.");
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanChangeStatus($request->user(), $user);

        $user->forceFill(['status' => UserStatus::Active])->save();

        return back()->with('status', "{$user->name} has been reactivated.");
    }

    /**
     * Nobody can change their own status, and only a super admin can change another super admin's.
     */
    protected function ensureCanChangeStatus(User $actor, User $target): void
    {
        abort_if($actor->is($target), 403, 'You cannot change the status of your own account.');

        abort_if(
            $target->hasRole(Role::SuperAdmin->value) && ! $actor->hasRole(Role::SuperAdmin->value),
            403,
            'Only a super admin can change another super admin.',
        );
    }
}
