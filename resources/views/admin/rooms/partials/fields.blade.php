@use('App\Enums\RoomStatus')
<div class="row g-3">
  <div class="col-sm-5">
    <label class="form-label" for="number">Room number</label>
    <input type="text" id="number" name="number" value="{{ old('number', $room->number) }}" maxlength="10"
           class="form-control @error('number') is-invalid @enderror" placeholder="e.g. 106" required>
    @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-sm-7">
    <label class="form-label" for="room_type_id">Room type</label>
    <select id="room_type_id" name="room_type_id" class="form-select @error('room_type_id') is-invalid @enderror" required>
      <option value="">Choose a type</option>
      @foreach ($roomTypes as $roomType)
        <option value="{{ $roomType->id }}" @selected((int) old('room_type_id', $room->room_type_id) === $roomType->id)>{{ $roomType->name }} · {{ $roomType->formattedPrice() }}</option>
      @endforeach
    </select>
    @error('room_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-12">
    <span class="form-label d-block">Status</span>
    <div class="choice-cards">
      @foreach ([
          [RoomStatus::Active, 'ti-circle-check', 'Bookable on the website.'],
          [RoomStatus::Maintenance, 'ti-tool', 'Temporarily out of sale. Existing bookings stay.'],
          [RoomStatus::Retired, 'ti-archive', 'Permanently out of use.'],
      ] as [$status, $icon, $hint])
        <label class="choice-card">
          <input type="radio" name="status" value="{{ $status->value }}" @checked(old('status', $room->status?->value) === $status->value)>
          <span>
            <i class="ti {{ $icon }}"></i>
            <strong>{{ $status->label() }}</strong>
            <small>{{ $hint }}</small>
          </span>
        </label>
      @endforeach
    </div>
    @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
  </div>
</div>
