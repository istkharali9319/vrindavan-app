<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="route('home')">
            {{ config('resort.name') }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            {{ config('resort.name') }} · {{ config('resort.address') }}
            {{ config('resort.phones.0.display') }} · {{ config('resort.email') }}
            © {{ date('Y') }} {{ config('resort.name') }}. All rights reserved.
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
