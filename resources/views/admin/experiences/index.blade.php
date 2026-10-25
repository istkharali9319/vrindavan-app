@extends('layouts.admin')

@section('title', 'Experiences & add-ons')
@section('subtitle', 'Everything guests can do or add to a stay. Choose where each one appears and set its price.')
@section('breadcrumb')
  <li class="breadcrumb-item" aria-current="page">Experiences &amp; add-ons</li>
@endsection

@section('actions')
  <a href="{{ route('experience') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener"><i class="ti ti-external-link"></i> Experiences page</a>
  <a href="{{ route('admin.experiences.create') }}" class="btn btn-primary"><i class="ti ti-plus"></i> Add experience or add-on</a>
@endsection

@section('content')
<div class="row g-3 mb-4">
  @foreach ([
      ['label' => 'In the catalogue', 'value' => $counts['all'], 'icon' => 'ti-list', 'tone' => 'is-indigo'],
      ['label' => 'On the Experiences page', 'value' => $counts['on_page'], 'icon' => 'ti-world', 'tone' => 'is-forest'],
      ['label' => 'Offered as booking add-ons', 'value' => $counts['add_ons'], 'icon' => 'ti-shopping-cart', 'tone' => 'is-bronze'],
      ['label' => 'Price on request', 'value' => $counts['on_request'], 'icon' => 'ti-currency-rupee', 'tone' => 'is-amber'],
  ] as $card)
    <div class="col-6 col-xl-3">
      <div class="website-summary-item {{ $card['tone'] }}">
        <i class="ti {{ $card['icon'] }}"></i>
        <div><strong>{{ $card['value'] }}</strong><span>{{ $card['label'] }}</span></div>
      </div>
    </div>
  @endforeach
</div>

@if ($experiences->isEmpty())
  <div class="card"><div class="card-body admin-empty"><i class="ti ti-flame"></i><p>Nothing here yet.</p><a href="{{ route('admin.experiences.create') }}" class="btn btn-primary">Add the first one</a></div></div>
@else
  <div class="row g-4">
    @foreach ($experiences as $experience)
      <div class="col-md-6 col-xxl-4">
        <article @class(['type-card', 'is-hidden' => ! $experience->is_active])>
          <div class="type-card-media">
            <img src="{{ $experience->imageUrl() }}" alt="{{ $experience->title }}" loading="lazy">
            <span class="type-card-order">#{{ $experience->sort_order }}</span>
            @unless ($experience->is_active)
              <span class="type-card-status"><i class="ti ti-eye-off"></i> Hidden</span>
            @endunless
            <div class="type-card-price">
              @if ($experience->price === null)
                <strong>On request</strong><span>no price set</span>
              @elseif ($experience->isComplimentary())
                <strong>Complimentary</strong><span>free for guests</span>
              @else
                <strong>{{ \App\Support\Money::format($experience->price) }}</strong><span>{{ $experience->price_unit->label() }}</span>
              @endif
            </div>
          </div>
          <div class="type-card-body">
            @if ($experience->kicker)
              <p class="type-card-tagline">{{ $experience->kicker }}</p>
            @endif
            <h5>{{ $experience->title }}</h5>
            <p class="type-card-desc">{{ \Illuminate\Support\Str::limit($experience->description, 110) }}</p>
            <div class="exp-flags">
              <span @class(['exp-flag', 'is-on' => $experience->show_on_page])><i class="ti {{ $experience->show_on_page ? 'ti-checks' : 'ti-minus' }}"></i> Experiences page</span>
              <span @class(['exp-flag', 'is-on' => $experience->offer_as_addon])><i class="ti {{ $experience->offer_as_addon ? 'ti-checks' : 'ti-minus' }}"></i> Booking add-on</span>
              @if ($sold[$experience->id] ?? 0)
                <span class="exp-flag is-info"><i class="ti ti-shopping-cart"></i> Added to {{ $sold[$experience->id] }} {{ str('booking')->plural($sold[$experience->id]) }}</span>
              @endif
            </div>
          </div>
          <div class="type-card-foot">
            <a href="{{ route('admin.experiences.edit', $experience) }}" class="btn btn-sm btn-primary"><i class="ti ti-pencil"></i> Edit</a>
            @if ($experience->show_on_page && $experience->is_active)
              <a href="{{ route('experience') }}#{{ $experience->slug }}" class="btn btn-sm btn-link ms-auto" target="_blank" rel="noopener">View <i class="ti ti-external-link"></i></a>
            @endif
          </div>
        </article>
      </div>
    @endforeach
  </div>
@endif
@endsection
