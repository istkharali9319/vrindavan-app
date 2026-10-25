@if ($user->trashed())
  <span class="status-pill is-deleted">Deleted</span>
@elseif ($user->isActive())
  <span class="status-pill is-active">Active</span>
@else
  <span class="status-pill is-suspended">Suspended</span>
@endif
