<x-mail::message>
Hello, {{ $name }}

{{ $message }}

<x-mail::panel>
    Email: <strong>{{ $email }}</strong>,<br>
    Password: <strong>{{ $password }}</strong>
</x-mail::panel>

<x-mail::button :url="$url">
Login
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
