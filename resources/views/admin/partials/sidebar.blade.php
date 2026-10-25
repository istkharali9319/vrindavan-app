@use('App\Enums\Permission')
@php
    /**
     * Sidebar groups. Items appear only when the signed-in staff member holds the permission.
     * Modules from later phases are added here as they are built.
     */
    $menu = [
        'Overview' => [
            ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'ti-dashboard', 'label' => 'Dashboard', 'can' => Permission::DashboardView],
        ],
        'Reservations' => [
            ['route' => 'admin.bookings.index', 'active' => 'admin.bookings.*', 'icon' => 'ti-calendar-event', 'label' => 'Bookings', 'can' => Permission::BookingsView],
            ['route' => 'admin.enquiries.index', 'active' => 'admin.enquiries.*', 'icon' => 'ti-message-circle', 'label' => 'Enquiries', 'can' => Permission::EnquiriesManage, 'badge' => fn () => \App\Models\Enquiry::unread()->count()],
        ],
        'Hotel' => [
            ['route' => 'admin.rooms.index', 'active' => 'admin.rooms.*', 'icon' => 'ti-key', 'label' => 'Rooms board', 'can' => Permission::InventoryView],
            ['route' => 'admin.room-types.index', 'active' => 'admin.room-types.*', 'icon' => 'ti-bed', 'label' => 'Room types', 'can' => Permission::CatalogManage],
            ['route' => 'admin.experiences.index', 'active' => 'admin.experiences.*', 'icon' => 'ti-flame', 'label' => 'Experiences & add-ons', 'can' => Permission::CatalogManage],
        ],
        'Website' => [
            ['route' => 'admin.website.index', 'active' => 'admin.website.*', 'icon' => 'ti-world', 'label' => 'Pages & content', 'can' => Permission::ContentManage],
        ],
        'People' => [
            ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'ti-users', 'label' => 'Users & customers', 'can' => Permission::UsersView],
            ['route' => 'admin.staff.index', 'active' => 'admin.staff.*', 'icon' => 'ti-shield-lock', 'label' => 'Staff & roles', 'can' => Permission::RolesManage],
        ],
    ];
@endphp
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header">
      <a href="{{ route('admin.dashboard') }}" class="b-brand admin-brand">
        <span class="admin-brand-mark" aria-hidden="true"><i class="ti ti-trees"></i></span>
        <span class="admin-brand-text">
          <span class="admin-brand-name">VRINDAVAN</span>
          <small>Resort admin</small>
        </span>
      </a>
    </div>
    <div class="navbar-content">
      <ul class="pc-navbar">
        @foreach ($menu as $group => $items)
          @php $visibleItems = array_filter($items, fn ($item) => auth()->user()->can($item['can']->value)); @endphp
          @continue(empty($visibleItems))
          <li class="pc-item pc-caption"><label>{{ $group }}</label></li>
          @foreach ($visibleItems as $item)
            <li @class(['pc-item', 'active' => request()->routeIs($item['active'])])>
              <a href="{{ route($item['route']) }}" class="pc-link">
                <span class="pc-micon"><i class="ti {{ $item['icon'] }}"></i></span>
                <span class="pc-mtext">{{ $item['label'] }}</span>
                @if (isset($item['badge']) && ($count = ($item['badge'])()) > 0)
                  <span class="pc-badge-count">{{ $count }}</span>
                @endif
              </a>
            </li>
          @endforeach
        @endforeach
      </ul>
    </div>
  </div>
</nav>
