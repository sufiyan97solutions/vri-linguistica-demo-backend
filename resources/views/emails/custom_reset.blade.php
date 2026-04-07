@component('mail::message')
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ config('app.name') }} Logo" style="width: 150px;">
</div>

# Hello, {{ $name }}

You are receiving this email because we received a password reset request for your account.

{{-- Custom Button --}}
<div style="text-align: center; margin: 20px 0;">
    <a href="{{ $url }}" style="
        display: inline-block;
        padding: 10px 18px;
        color: white !important;
        font-weight: bold;
        background-color: #6f8dff;
        border: 8px solid #6f8dff;
        text-decoration: none;
        border-radius: 5px;
    ">
        Reset Password
    </a>
</div>

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
