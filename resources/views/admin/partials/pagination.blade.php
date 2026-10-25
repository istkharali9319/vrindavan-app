{{-- Footer for a paginated admin list: where you are, plus page controls (greyed out when there is only one page). --}}
@if ($paginator->total() > 0)
  <div class="admin-pager">
    <span class="admin-pager-info">
      Page <strong>{{ $paginator->currentPage() }}</strong> of <strong>{{ $paginator->lastPage() }}</strong>
      · {{ number_format($paginator->total()) }} {{ \Illuminate\Support\Str::plural($noun, $paginator->total()) }}
      · {{ $paginator->perPage() }} per page
    </span>
    @if ($paginator->hasPages())
      <div class="admin-pagination">{{ $paginator->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    @else
      <nav class="admin-pagination" aria-label="Pages">
        <ul class="pagination mb-0">
          <li class="page-item disabled" aria-disabled="true"><span class="page-link">‹ Previous</span></li>
          <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
          <li class="page-item disabled" aria-disabled="true"><span class="page-link">Next ›</span></li>
        </ul>
      </nav>
    @endif
  </div>
@endif
