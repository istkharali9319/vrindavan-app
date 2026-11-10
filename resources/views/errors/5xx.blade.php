{{-- Any other server error without its own page (e.g. 502, 504): the standalone branded page. --}}
@include('errors.minimal-brand', [
    'code' => $exception->getStatusCode(),
    'title' => 'Something went wrong on our side',
    'text' => 'We’re sorry — this page hit a problem. Please try again in a moment. If it keeps happening, call us and we’ll help you directly.',
])
