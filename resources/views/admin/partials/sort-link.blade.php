{{-- Sortable column heading. Clicking the active column flips the direction; a new column starts ascending (dates start newest first). --}}
@php
    $isActive = $sort === $column;
    $nextDirection = $isActive
        ? ($direction === 'asc' ? 'desc' : 'asc')
        : ($defaultDirection ?? 'asc');
    $url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection, 'page' => null]);
@endphp
<a href="{{ $url }}" @class(['sort-link', 'active' => $isActive])
   @if ($isActive) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
  {{ $label }}
  @if ($isActive)
    <i class="ti {{ $direction === 'asc' ? 'ti-arrow-up' : 'ti-arrow-down' }}"></i>
  @else
    <i class="ti ti-arrows-sort"></i>
  @endif
</a>
