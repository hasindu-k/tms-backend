@extends('emails.layout')

@section('content')
    <h1>Verify Your Email Address</h1>
    <p>Hello, {{ $user->name }}!</p>
    <p>Please click the button below to verify your email address:</p>

    <a href="{{ $verificationUrl }}"
        style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        Verify Email
    </a>

    <p>If you are having trouble clicking the "Verify Email" button, copy and paste the URL below into your web browser:</p>

    <p><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>

    <p>If you did not create an account, no further action is required.</p>
    <p>Thank you for registering with {{ config('app.name') }}!</p>
@endsection
