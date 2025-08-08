@extends('emails.layout') {{-- Extending the base layout --}}

@section('content')
    <h1>New User Registration</h1>
    <p>A new user has registered on the platform.</p>
    <h3>User Details:</h3>
    <ul>
        <li><strong>Name:</strong> {{ $user->name }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
    </ul>
    <p>Please review the new user's details at your convenience.</p>
    <a href="{{ url('/users/' . $user->id) }}"
        style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View
        User</a>
    <p>Thank you for your attention!</p>
@endsection
