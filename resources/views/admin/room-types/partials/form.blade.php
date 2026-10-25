@php
    $price = old('price', $roomType->nightly_price ? rtrim(rtrim(number_format($roomType->nightly_price / 100, 2, '.', ''), '0'), '.') : '');
    $amenities = old('amenities', implode("\n", $roomType->amenityList()));
    $amenities = is_array($amenities) ? implode("\n", $amenities) : $amenities;
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate>
  @csrf
  @if ($roomType->exists)
    @method('PUT')
  @endif

  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Details</h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label" for="name">Name</label>
              <input type="text" id="name" name="name" value="{{ old('name', $roomType->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Kanchenjunga Suite" required>
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-5">
              <label class="form-label" for="slug">Web address</label>
              <div class="input-group">
                <span class="input-group-text">/book/</span>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $roomType->slug) }}" class="form-control @error('slug') is-invalid @enderror" placeholder="made from the name">
                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
            <div class="col-md-7">
              <label class="form-label" for="tagline">Tagline</label>
              <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $roomType->tagline) }}" class="form-control @error('tagline') is-invalid @enderror" placeholder="e.g. High altitude panorama">
              @error('tagline')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-5">
              <label class="form-label" for="badge">Photo badge</label>
              <input type="text" id="badge" name="badge" value="{{ old('badge', $roomType->badge) }}" maxlength="40" class="form-control @error('badge') is-invalid @enderror" placeholder="e.g. Mountain Facing">
              @error('badge')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
              <label class="form-label" for="description">Description</label>
              <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror" required>{{ old('description', $roomType->description) }}</textarea>
              @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
              <label class="form-label" for="amenities">Highlights <small class="text-muted fw-normal">one per line, shown as a list on the website</small></label>
              <textarea id="amenities" name="amenities" rows="5" class="form-control @error('amenities') is-invalid @enderror @error('amenities.*') is-invalid @enderror" placeholder="Private glass balcony&#10;Rain shower&#10;Temi tea station">{{ $amenities }}</textarea>
              @error('amenities')<div class="invalid-feedback">{{ $message }}</div>@enderror
              @error('amenities.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Photos</h5></div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-md-5">
              <span class="form-label d-block">Main photo</span>
              <label class="photo-drop" for="image">
                <img src="{{ $roomType->imageUrl() }}" alt="" data-image-preview>
                <span class="photo-drop-hint"><i class="ti ti-upload"></i> {{ $roomType->image_path ? 'Replace photo' : 'Upload photo' }}</span>
              </label>
              <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="visually-hidden" data-image-input>
              <small class="text-muted d-block mt-2">JPG, PNG or WebP up to 5 MB. Landscape works best.</small>
              @error('image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-7">
              <span class="form-label d-block">Gallery <small class="text-muted fw-normal">up to {{ \App\Http\Requests\Admin\RoomTypeRequest::MAX_GALLERY_PHOTOS }}, shown under the room on the website</small></span>
              @if ($roomType->gallery)
                <div class="gallery-grid mb-3">
                  @foreach ($roomType->gallery as $index => $path)
                    <label class="gallery-item">
                      <img src="{{ asset('storage/'.$path) }}" alt="" loading="lazy">
                      <input type="checkbox" name="remove_gallery[]" value="{{ $path }}" class="form-check-input">
                      <span class="gallery-remove"><i class="ti ti-trash"></i> Remove</span>
                    </label>
                  @endforeach
                </div>
              @endif
              <input type="file" id="gallery" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple class="form-control @error('gallery') is-invalid @enderror @error('gallery.*') is-invalid @enderror">
              @error('gallery')<div class="invalid-feedback">{{ $message }}</div>@enderror
              @error('gallery.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card mb-4 sticky-xl-top" style="top: 90px">
        <div class="card-header"><h5 class="mb-0">Tariff &amp; capacity</h5></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="price">Nightly tariff</label>
            <div class="input-group">
              <span class="input-group-text">₹</span>
              <input type="number" id="price" name="price" value="{{ $price }}" min="1" step="0.01" class="form-control @error('price') is-invalid @enderror" required>
              @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <small class="text-muted">Per room per night, including GST.</small>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label" for="max_adults">Max adults</label>
              <input type="number" id="max_adults" name="max_adults" value="{{ old('max_adults', $roomType->max_adults) }}" min="1" max="12" class="form-control @error('max_adults') is-invalid @enderror" required>
              @error('max_adults')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-6">
              <label class="form-label" for="max_children">Max children</label>
              <input type="number" id="max_children" name="max_children" value="{{ old('max_children', $roomType->max_children) }}" min="0" max="8" class="form-control @error('max_children') is-invalid @enderror" required>
              @error('max_children')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="sort_order">Display order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $roomType->sort_order ?? 0) }}" min="0" max="999" class="form-control @error('sort_order') is-invalid @enderror">
            <small class="text-muted">Lower numbers come first on the website.</small>
            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="form-check form-switch admin-switch mb-4">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $roomType->is_active))>
            <label class="form-check-label" for="is_active">
              <strong>Show on website</strong>
              <small>Guests can see and book this type.</small>
            </label>
          </div>
          @if ($roomType->exists)
            <p class="text-muted small mb-3">{{ $roomType->rooms_count }} {{ str('room')->plural($roomType->rooms_count) }} · {{ $roomType->bookings_count }} {{ str('booking')->plural($roomType->bookings_count) }} so far. Tariff changes don't affect existing bookings.</p>
          @endif
          <button type="submit" class="btn btn-primary w-100">{{ $submitLabel }}</button>
          <a href="{{ route('admin.room-types.index') }}" class="btn btn-link w-100 mt-1">Cancel</a>
        </div>
      </div>
    </div>
  </div>
</form>

@push('scripts')
  <script>
    document.querySelectorAll('[data-image-input]').forEach((input) => {
      input.addEventListener('change', () => {
        const [file] = input.files;
        const preview = document.querySelector('[data-image-preview]');
        if (file && preview) {
          preview.src = URL.createObjectURL(file);
        }
      });
    });
  </script>
@endpush
