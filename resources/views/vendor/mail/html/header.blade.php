@props(['url'])
@php($logo = \App\Support\MailLogo::src())
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logo)
<img src="{{ $logo }}" class="logo" width="200" alt="{{ config('resort.name') }}">
@else
<span class="brand-name">{!! $slot !!}</span>
@endif
</a>
@if (config('resort.tagline'))
<p class="tagline">{{ config('resort.tagline') }}</p>
@endif
</td>
</tr>
