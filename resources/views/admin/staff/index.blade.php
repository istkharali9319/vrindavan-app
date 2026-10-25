@extends('layouts.admin')

@section('title', 'Staff & roles')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">Staff</li>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Team</h5>
    <a href="{{ route('admin.staff.create') }}" class="btn btn-primary"><i class="ti ti-user-plus"></i> Add staff member</a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Two-factor</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
        <tbody>
          @foreach ($staff as $member)
            <tr>
              <td><a href="{{ route('admin.users.show', $member) }}">{{ $member->name }}</a></td>
              <td>{{ $member->email }}</td>
              <td>{{ $member->roles->map(fn ($role) => \App\Enums\Role::from($role->name)->label())->implode(', ') }}</td>
              <td>
                @if ($member->hasConfirmedTwoFactor())
                  <span class="badge bg-light-success text-success">On</span>
                @else
                  <span class="badge bg-light-warning text-warning">Not set up</span>
                @endif
              </td>
              <td>@include('admin.users.partials.status-badge', ['user' => $member])</td>
              <td>{{ $member->last_login_at?->timezone(config('booking.timezone'))->format('d M Y, H:i') ?? 'Never' }}</td>
              <td class="text-end"><a href="{{ route('admin.staff.edit', $member) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5>What each role can do</h5>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>Permission</th>
            @foreach (\App\Enums\Role::staff() as $role)
              <th class="text-center">{{ $role->label() }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach (\App\Enums\Permission::cases() as $permission)
            <tr>
              <td><code>{{ $permission->value }}</code></td>
              @foreach (\App\Enums\Role::staff() as $role)
                <td class="text-center">{!! in_array($permission, $role->permissions(), true) ? '<i class="ti ti-check text-success"></i>' : '<span class="text-muted">—</span>' !!}</td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
