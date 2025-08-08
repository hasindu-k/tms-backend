@extends('emails.layout')

@section('content')
    <p>Hello, {{ $user->name }}!</p>

    <p>You have requested to reset your password. Please click the button below to reset it:</p>

    <a href="{{ $resetUrl }}"
        style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        Reset Password
    </a>

    <p>If you did not request a password reset, no further action is required.</p>

    <p>If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
    </p>

    <p><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>

    <p>Thank you,</p>
    <p>The {{ config('app.name') }} Team</p>
@endsection
