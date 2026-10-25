<div class="mb-3">
  <label class="form-label" for="name">Full name</label>
  <input type="text" id="name" name="name" value="{{ old('name', $member?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
  @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
  <label class="form-label" for="phone">Mobile (optional)</label>
  <input type="tel" id="phone" name="phone" value="{{ old('phone', $member?->phone) }}" class="form-control @error('phone') is-invalid @enderror">
  @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
  <label class="form-label" for="role">Role</label>
  <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
    @foreach ($roles as $role)
      <option value="{{ $role->value }}" @selected(old('role', $member?->roles->first()?->name) === $role->value)>{{ $role->label() }}</option>
    @endforeach
  </select>
  @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
