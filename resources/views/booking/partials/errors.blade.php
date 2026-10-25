@if ($errors->any())
  <div class="flash error" role="alert">
    @foreach ($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif
