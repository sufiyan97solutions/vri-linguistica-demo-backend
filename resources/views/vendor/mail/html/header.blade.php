@props(['url'])
<div style="margin-top: 20px;padding-top:10px">
    <tr style="display: none">
        <td class="header">
            <a href="{{ $url }}" style="display: inline-block;">
                @if (trim($slot) === 'Laravel')
                <img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
                @else
                {{ $slot }}
                @endif
            </a>
        </td>
    </tr>
</div>
