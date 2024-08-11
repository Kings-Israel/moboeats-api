@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
                <img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
            @else
                <img style="width: 100px; object-fit:contain;" alt="Mobo Eats" src="{{ asset('assets/img/1024.png') }}">
            @endif
        </a>
    </td>
</tr>
