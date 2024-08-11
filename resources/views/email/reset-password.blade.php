<x-mail::message>
# Hello

Here's is your code to reset your password.

<x-mail::panel>
    <p style="font-weight: 800">
        {{ $code }}
    </p>
</x-mail::panel>

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
