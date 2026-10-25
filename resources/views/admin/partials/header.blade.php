@php
    $admin = auth()->user();
    $unreadCount = $admin->unreadNotifications()->count();
    $latestNotifications = $admin->notifications()->limit(6)->get();
@endphp
<header class="pc-header">
  <div class="header-wrapper">
    <div class="me-auto pc-mob-drp">
      <ul class="list-unstyled">
        <li class="pc-h-item pc-sidebar-collapse">
          <a href="#" class="pc-head-link ms-0" id="sidebar-hide" aria-label="Collapse menu"><i class="ti ti-menu-2"></i></a>
        </li>
        <li class="pc-h-item pc-sidebar-popup">
          <a href="#" class="pc-head-link ms-0" id="mobile-collapse" aria-label="Open menu"><i class="ti ti-menu-2"></i></a>
        </li>
      </ul>
    </div>
    <div class="ms-auto">
      <ul class="list-unstyled">
        <li class="pc-h-item d-none d-md-inline-flex">
          <span class="admin-header-date"><i class="ti ti-calendar"></i> {{ now(config('booking.timezone'))->format('D, j M Y') }}</span>
        </li>
        <li class="dropdown pc-h-item header-notifications">
          <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" aria-label="Notifications{{ $unreadCount ? ', '.$unreadCount.' unread' : '' }}">
            <i class="ti ti-bell"></i>
            @if ($unreadCount)
              <span class="bell-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
            @endif
          </a>
          <div class="dropdown-menu dropdown-menu-end pc-h-dropdown notice-dropdown">
            <div class="notice-dropdown-head">
              <strong>Notifications</strong>
              @if ($unreadCount)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                  @csrf
                  <button type="submit" class="btn btn-link btn-sm p-0">Mark all read</button>
                </form>
              @endif
            </div>
            <div class="notice-dropdown-list">
              @forelse ($latestNotifications as $notification)
                @include('partials.notification-item', ['notification' => $notification, 'readRoute' => 'admin.notifications.read', 'compact' => true])
              @empty
                <p class="notice-empty"><i class="ti ti-bell-off"></i> You're all caught up.</p>
              @endforelse
            </div>
            <a href="{{ route('admin.notifications.index') }}" class="notice-dropdown-all">View all notifications</a>
          </div>
        </li>
        <li class="dropdown pc-h-item header-user-profile">
          <a class="pc-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#" role="button"
             aria-haspopup="false" data-bs-auto-close="outside" aria-expanded="false">
            <span class="admin-avatar">{{ $admin->initials() }}</span>
            <span>{{ $admin->name }}</span>
          </a>
          <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
            <div class="dropdown-header">
              <div class="d-flex mb-1 align-items-center">
                <span class="admin-avatar me-2">{{ $admin->initials() }}</span>
                <div class="flex-grow-1">
                  <h6 class="mb-0">{{ $admin->name }}</h6>
                  <span class="text-muted">{{ $admin->getRoleNames()->map(fn ($role) => \App\Enums\Role::from($role)->label())->implode(', ') }}</span>
                </div>
              </div>
            </div>
            <a href="{{ route('admin.profile') }}" class="dropdown-item"><i class="ti ti-user"></i> <span>Profile &amp; password</span></a>
            <a href="{{ route('home') }}" class="dropdown-item" target="_blank" rel="noopener"><i class="ti ti-world"></i> <span>View website</span></a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <input type="hidden" name="portal" value="admin">
              <button type="submit" class="dropdown-item"><i class="ti ti-power"></i> <span>Sign out</span></button>
            </form>
          </div>
        </li>
      </ul>
    </div>
  </div>
</header>
