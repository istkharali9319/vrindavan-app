@extends('layouts.admin')

@section('title', 'Users & customers')
@section('subtitle', 'Everyone with an account: guests who book online and the resort team.')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">Users</li>
@endsection

@section('actions')
  @can(\App\Enums\Permission::RolesManage->value)
    <a href="{{ route('admin.staff.create') }}" class="btn btn-primary"><i class="ti ti-user-plus"></i> Add staff</a>
  @endcan
@endsection

@section('content')
<div class="row g-3 mb-4">
  @foreach ([
      ['label' => 'All users', 'value' => $counts['all'], 'icon' => 'ti-users', 'tone' => 'is-indigo', 'query' => []],
      ['label' => 'Customers', 'value' => $counts['customers'], 'icon' => 'ti-user-check', 'tone' => 'is-forest', 'query' => ['type' => 'customer']],
      ['label' => 'Staff', 'value' => $counts['staff'], 'icon' => 'ti-shield-check', 'tone' => 'is-teal', 'query' => ['type' => 'staff']],
      ['label' => 'Suspended', 'value' => $counts['suspended'], 'icon' => 'ti-user-off', 'tone' => 'is-rose', 'query' => ['status' => 'suspended']],
  ] as $card)
    <div class="col-6 col-xl-3">
      <a href="{{ route('admin.users.index', $card['query']) }}" class="website-summary-item user-count {{ $card['tone'] }}">
        <i class="ti {{ $card['icon'] }}"></i>
        <div><strong>{{ number_format($card['value']) }}</strong><span>{{ $card['label'] }}</span></div>
      </a>
    </div>
  @endforeach
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end filters-bar mb-3" data-auto-submit>
      <input type="hidden" name="sort" value="{{ $sort }}">
      <input type="hidden" name="direction" value="{{ $direction }}">
      <div class="col-xl-4 col-lg-12">
        <label class="form-label" for="search">Search</label>
        <div class="search-field">
          <i class="ti ti-search"></i>
          <input type="search" id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                 placeholder="Name, email or mobile" autocomplete="off" data-live-search>
        </div>
      </div>
      <div class="col-xl-2 col-lg-3 col-sm-6">
        <label class="form-label" for="type">Type</label>
        <select id="type" name="type" class="form-select">
          <option value="">All</option>
          <option value="customer" @selected(($filters['type'] ?? '') === 'customer')>Customers</option>
          <option value="staff" @selected(($filters['type'] ?? '') === 'staff')>Staff</option>
        </select>
      </div>
      <div class="col-xl-2 col-lg-3 col-sm-6">
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="form-select">
          <option value="">Any</option>
          <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
          <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspended</option>
          <option value="deleted" @selected(($filters['status'] ?? '') === 'deleted')>Deleted</option>
        </select>
      </div>
      <div class="col-xl-2 col-lg-3 col-sm-6">
        <label class="form-label" for="per_page">Rows</label>
        <select id="per_page" name="per_page" class="form-select">
          @foreach (\App\Http\Controllers\Admin\UserController::PAGE_SIZES as $size)
            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} per page</option>
          @endforeach
        </select>
      </div>
      <div class="col-xl-2 col-lg-3 col-sm-6 d-flex gap-2">
        <noscript><button type="submit" class="btn btn-primary">Filter</button></noscript>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
      </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2 results-summary">
      <span class="text-muted">
        @if ($users->total() > 0)
          Showing <strong>{{ number_format($users->firstItem()) }}–{{ number_format($users->lastItem()) }}</strong>
          of <strong>{{ number_format($users->total()) }}</strong> {{ \Illuminate\Support\Str::plural('user', $users->total()) }}
        @else
          No results
        @endif
        @if (! empty($filters['search']))
          for “{{ $filters['search'] }}”
        @endif
      </span>
    </div>

    <div class="table-responsive">
      <table class="table mb-0 user-table">
        <thead>
          <tr>
            <th>@include('admin.partials.sort-link', ['column' => 'name', 'label' => 'Name'])</th>
            <th>@include('admin.partials.sort-link', ['column' => 'email', 'label' => 'Email'])</th>
            <th>Mobile</th>
            <th>Role</th>
            <th>Status</th>
            <th>@include('admin.partials.sort-link', ['column' => 'joined', 'label' => 'Joined', 'defaultDirection' => 'desc'])</th>
            <th>@include('admin.partials.sort-link', ['column' => 'last_login', 'label' => 'Last sign-in', 'defaultDirection' => 'desc'])</th>
            <th class="text-end"><span class="visually-hidden">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $user)
            @php
                $roles = $user->roles->map(fn ($role) => \App\Enums\Role::from($role->name));
                $isStaff = $roles->contains(fn ($role) => $role->isStaff());
            @endphp
            <tr @class(['is-muted' => $user->trashed()])>
              <td>
                <a href="{{ route('admin.users.show', $user) }}" class="user-cell">
                  <span @class(['user-avatar', 'is-staff' => $isStaff])>{{ $user->initials() }}</span>
                  <strong>{{ $user->name }}</strong>
                </a>
              </td>
              <td class="user-email">{{ $user->email }}</td>
              <td class="text-nowrap">{{ $user->phone ?? '—' }}</td>
              <td>
                @forelse ($roles as $role)
                  <span @class(['role-chip', 'is-staff' => $role->isStaff()])>
                    <i class="ti {{ $role->isStaff() ? 'ti-shield-check' : 'ti-user' }}"></i> {{ $role->label() }}
                  </span>
                @empty
                  —
                @endforelse
              </td>
              <td>@include('admin.users.partials.status-badge', ['user' => $user])</td>
              <td class="text-nowrap">{{ $user->created_at->timezone(config('booking.timezone'))->format('d M Y') }}</td>
              <td class="text-nowrap">
                @if ($user->last_login_at)
                  {{ $user->last_login_at->timezone(config('booking.timezone'))->format('d M Y') }}
                  <small class="d-block text-muted">{{ $user->last_login_at->diffForHumans() }}</small>
                @else
                  <span class="text-muted">Never</span>
                @endif
              </td>
              <td class="text-end">
                <a href="{{ route('admin.users.show', $user) }}" class="btn-icon d-inline-grid" aria-label="View {{ $user->name }}"><i class="ti ti-chevron-right"></i></a>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-5">No users match these filters.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $users, 'noun' => 'user'])
  </div>
</div>
@endsection

@push('scripts')
  @vite(['resources/js/admin.js'])
@endpush
