{{--
  One editable field from config/site-content.php.
  $field (type, label, help, options, default), $value, $id, $key (dot path for old()/errors),
  $name (input name), $imageName and $resetName (images only; $resetName may be null).
--}}
@php
    $old = old($key, $value);
    $error = $errors->first($field['type'] === 'image' ? str_replace(['[', ']'], ['.', ''], $imageName) : $key);
    $isWide = in_array($field['type'], ['textarea', 'list', 'image', 'tags'], true);
@endphp
<div @class(['col-12' => $isWide, 'col-md-6' => ! $isWide])>
  @if ($field['type'] === 'image')
    <span class="form-label d-block">{{ $field['label'] }}</span>
    <div class="content-image">
      <label @class(['photo-drop', 'is-contain' => \Illuminate\Support\Str::contains($id, ['logo', 'favicon'])]) for="{{ $id }}">
        <img src="{{ \App\Support\WebsiteContent::imageUrl($value) }}" alt="" data-image-preview="{{ $id }}">
        <span class="photo-drop-hint"><i class="ti ti-upload"></i> {{ $value ? 'Replace photo' : 'Upload photo' }}</span>
      </label>
      <div class="content-image-meta">
        <input type="file" id="{{ $id }}" name="{{ $imageName }}" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm @if ($error) is-invalid @endif" data-image-input="{{ $id }}">
        <small class="text-muted">JPG, PNG or WebP up to 5 MB.</small>
        @if ($resetName && \App\Support\ContentFieldRules::isUpload($value))
          <label class="form-check mt-2 mb-0">
            <input type="checkbox" name="{{ $resetName }}" value="1" class="form-check-input">
            <span class="form-check-label small">Remove my upload and use the original photo</span>
          </label>
        @endif
        @if ($error)<div class="invalid-feedback d-block">{{ $error }}</div>@endif
      </div>
    </div>
  @else
    <label class="form-label" for="{{ $id }}">{{ $field['label'] }}</label>
    @switch($field['type'])
      @case('textarea')
      @case('list')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $field['type'] === 'list' ? 4 : max(2, min(8, (int) ceil(mb_strlen((string) $old) / 90) + 1)) }}"
                  class="form-control @if ($error) is-invalid @endif">{{ is_array($old) ? implode("\n", $old) : $old }}</textarea>
        @break
      @case('select')
      @case('icon')
        <select id="{{ $id }}" name="{{ $name }}" class="form-select @if ($error) is-invalid @endif" @if ($field['type'] === 'icon') data-icon-select @endif>
          @foreach ($field['options'] as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $old === (string) $optionValue)>{{ $optionLabel }}</option>
          @endforeach
        </select>
        @break
      @case('tags')
        @php $selected = is_array($old) ? $old : \App\Support\WebsiteContent::lines($old); @endphp
        <div class="content-tags">
          @foreach ($field['options'] as $optionValue => $optionLabel)
            <label class="content-tag">
              <input type="checkbox" name="{{ $name }}[]" value="{{ $optionValue }}" @checked(in_array($optionValue, $selected, true))>
              <span>{{ $optionLabel }}</span>
            </label>
          @endforeach
        </div>
        @break
      @default
        <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $old }}" class="form-control @if ($error) is-invalid @endif">
    @endswitch
    @if ($error)<div class="invalid-feedback d-block">{{ $error }}</div>@endif
  @endif
  @if ($field['help'])
    <small class="text-muted d-block mt-1">{{ $field['help'] }}</small>
  @endif
</div>
