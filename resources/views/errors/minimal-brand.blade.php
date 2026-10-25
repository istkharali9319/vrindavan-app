{{-- Standalone page for server errors: no database, no build assets, so it still shows when something is broken. --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>{{ $title }} · {{ config('app.name') }}</title>
  <style>
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;font-family:"Helvetica Neue",Arial,sans-serif;color:#1d201d;background:radial-gradient(circle at 15% 20%,rgba(201,162,74,.16),transparent 42%),linear-gradient(180deg,#fbf9f5,#f1ede5)}
    .card{max-width:560px;width:100%;padding:48px 40px 36px;text-align:center;background:#fff;border:1px solid #e4ded3;border-top:4px solid #c9a24a;border-radius:24px;box-shadow:0 18px 40px rgba(20,35,27,.12)}
    img{width:190px;height:auto;margin-bottom:26px}
    .code{margin:0 0 8px;font-size:12px;font-weight:600;letter-spacing:.24em;text-transform:uppercase;color:#a87b45}
    h1{margin:0 0 14px;font-family:Georgia,"Times New Roman",serif;font-size:34px;font-weight:normal;line-height:1.15}
    p{margin:0;color:#6b6a64;line-height:1.7}
    a.btn{display:inline-block;margin-top:28px;padding:14px 30px;border-radius:999px;background:#14231b;color:#fff;font-size:13px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;text-decoration:none}
  </style>
</head>
<body>
  <div class="card">
    <img src="{{ asset('images/brand/logo.png') }}" alt="{{ config('app.name') }}">
    <p class="code">Error {{ $code }}</p>
    <h1>{{ $title }}</h1>
    <p>{{ $text }}</p>
    <a class="btn" href="{{ url('/') }}">Back to home</a>
  </div>
</body>
</html>
