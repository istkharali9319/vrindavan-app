@use('App\Enums\PriceUnit')
@use('App\Models\Experience')
@php
    $price = old('price', $experience->price === null ? '' : rtrim(rtrim(number_format($experience->price / 100, 2, '.', ''), '0'), '.'));
    $highlights = old('highlights', implode("\n", $experience->highlightList()));
    $highlights = is_array($highlights) ? implode("\n", $highlights) : $highlights;
    $selectedCategories = old('categories', $experience->categories ?? []);
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate>
  @csrf
  @if ($experience->exists)
    @method('PUT')
  @endif

  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Details</h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label" for="title">Title</label>
              <input type="text" id="title" name="title" value="{{ old('title', $experience->title) }}" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Airport pickup from Bagdogra" required>
              @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
              <label class="form-label" for="slug">Link name</label>
              <input type="text" id="slug" name="slug" value="{{ old('slug', $experience->slug) }}" class="form-control @error('slug') is-invalid @enderror" placeholder="made from the title">
              @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="kicker">Small label above the title</label>
              <input type="text" id="kicker" name="kicker" value="{{ old('kicker', $experience->kicker) }}" class="form-control" placeholder="e.g. Culinary heritage">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="badge">Badge on the photo</label>
              <input type="text" id="badge" name="badge" value="{{ old('badge', $experience->badge) }}" class="form-control" placeholder="e.g. Garden to table">
            </div>
            <div class="col-12">
              <label class="form-label" for="description">Description</label>
              <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $experience->description) }}</textarea>
              @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
              <label class="form-label" for="highlights">Highlights <small class="text-muted fw-normal">one per line</small></label>
              <textarea id="highlights" name="highlights" rows="4" class="form-control @error('highlights') is-invalid @enderror @error('highlights.*') is-invalid @enderror">{{ $highlights }}</textarea>
              @error('highlights')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="schedule">Timing</label>
              <input type="text" id="schedule" name="schedule" value="{{ old('schedule', $experience->schedule) }}" class="form-control" placeholder="e.g. Daily: 7:00 PM – 9:30 PM">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="link_label">Link label</label>
              <input type="text" id="link_label" name="link_label" value="{{ old('link_label', $experience->link_label) }}" class="form-control" placeholder="Inquire & book">
            </div>
            <div class="col-12">
              <span class="form-label d-block">Categories (filters on the Experiences page)</span>
              <div class="content-tags">
                @foreach (Experience::CATEGORIES as $value => $label)
                  <label class="content-tag">
                    <input type="checkbox" name="categories[]" value="{{ $value }}" @checked(in_array($value, $selectedCategories, true))>
                    <span>{{ $label }}</span>
                  </label>
                @endforeach
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="layout">Layout on the Experiences page</label>
              <select id="layout" name="layout" class="form-select">
                <option value="feature" @selected(old('layout', $experience->layout) === 'feature')>Large row</option>
                <option value="card" @selected(old('layout', $experience->layout) === 'card')>Card (two per row)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Photo</h5></div>
        <div class="card-body">
          <div class="content-image">
            <label class="photo-drop" for="image">
              <img src="{{ $experience->imageUrl() }}" alt="" data-image-preview="image">
              <span class="photo-drop-hint"><i class="ti ti-upload"></i> {{ $experience->image_path ? 'Replace photo' : 'Upload photo' }}</span>
            </label>
            <div class="content-image-meta">
              <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm @error('image') is-invalid @enderror" data-image-input="image">
              <small class="text-muted">JPG, PNG or WebP up to 5 MB.</small>
              @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card mb-4 sticky-xl-top" style="top: 90px">
        <div class="card-header"><h5 class="mb-0">Price &amp; where it appears</h5></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="price">Price</label>
            <div class="input-group">
              <span class="input-group-text">₹</span>
              <input type="number" id="price" name="price" value="{{ $price }}" min="0" step="0.01" class="form-control @error('price') is-invalid @enderror" placeholder="Leave empty = on request">
              @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <small class="text-muted">Including GST. 0 = complimentary. Empty = "price on request".</small>
          </div>
          <div class="mb-3">
            <label class="form-label" for="price_unit">Charged</label>
            <select id="price_unit" name="price_unit" class="form-select">
              @foreach (PriceUnit::cases() as $unit)
                <option value="{{ $unit->value }}" @selected(old('price_unit', $experience->price_unit?->value) === $unit->value)>{{ ucfirst($unit->label()) }}</option>
              @endforeach
            </select>
            <small class="text-muted">For add-ons, guests are all adults and children on the booking.</small>
          </div>

          <div class="form-check form-switch admin-switch mb-2">
            <input type="hidden" name="show_on_page" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="show_on_page" name="show_on_page" value="1" @checked(old('show_on_page', $experience->show_on_page))>
            <label class="form-check-label" for="show_on_page"><strong>Show on Experiences page</strong><small>Listed with its price on /experiences.</small></label>
          </div>
          <div class="form-check form-switch admin-switch mb-2">
            <input type="hidden" name="offer_as_addon" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="offer_as_addon" name="offer_as_addon" value="1" @checked(old('offer_as_addon', $experience->offer_as_addon))>
            <label class="form-check-label" for="offer_as_addon"><strong>Offer as a booking add-on</strong><small>Guests can tick it while booking a room. Needs a price.</small></label>
          </div>
          <div class="form-check form-switch admin-switch mb-3">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $experience->is_active))>
            <label class="form-check-label" for="is_active"><strong>Active</strong><small>Turn off to hide it everywhere without deleting.</small></label>
          </div>

          <div class="mb-4">
            <label class="form-label" for="sort_order">Display order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $experience->sort_order) }}" min="0" max="999" class="form-control">
            <small class="text-muted">Lower numbers come first.</small>
          </div>

          <button type="submit" class="btn btn-primary w-100">{{ $submitLabel }}</button>
          <a href="{{ route('admin.experiences.index') }}" class="btn btn-link w-100 mt-1">Cancel</a>
        </div>
      </div>
    </div>
  </div>
</form>

@push('scripts')
  @include('admin.website.partials.preview-script')
@endpush
