<x-mail::message>
@if ($name)
New Message from {{ $name }}
@endif

<x-mail::panel>
    {{ $message }}
</x-mail::panel>

{{ config('app.name') }}
</x-mail::message>
