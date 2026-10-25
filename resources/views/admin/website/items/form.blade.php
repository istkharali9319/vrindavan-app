<form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate>
  @csrf
  @if ($item->exists)
    @method('PUT')
  @endif

  <div class="card">
    <div class="card-body">
      <div class="row g-3">
        @foreach ($collection['fields'] as $fieldKey => $field)
          @include('admin.website.partials.field', [
              'field' => $field,
              'value' => $field['type'] === 'tags' ? ($item->data[$fieldKey] ?? []) : $item->get($fieldKey),
              'id' => "item-{$fieldKey}",
              'key' => "data.{$fieldKey}",
              'name' => "data[{$fieldKey}]",
              'imageName' => "images[{$fieldKey}]",
              'resetName' => null,
          ])
        @endforeach
        <div class="col-12">
          <div class="form-check form-switch admin-switch">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $item->is_active))>
            <label class="form-check-label" for="is_active">
              <strong>Show on website</strong>
              <small>Turn off to hide it without deleting.</small>
            </label>
          </div>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('admin.website.items.index', [$pageKey, $collectionKey]) }}" class="btn btn-link">Cancel</a>
      </div>
    </div>
  </div>
</form>

@push('scripts')
  @include('admin.website.partials.preview-script')
@endpush
