@extends('emails.layout')

@section('content')
    <h1>Welcome to our application, {{ $notifiable->name }}!</h1>
    <p>We're excited to have you on board. Your account has been successfully verified, and you're ready to start using our
        application.</p>
    <p>Click the button below to get started and explore the features:</p>
    <a href="{{ url('/') }}"
        style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Get
        Started</a>
    <p>Thank you for joining us!</p>
@endsection
