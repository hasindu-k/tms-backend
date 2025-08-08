@extends('emails.layout')

@section('content')
    <h1>New Task Assigned</h1>
    <p>You have been assigned a new task:</p>
    <h3>{{ $task->title }}</h3>
    <p>{{ $task->description }}</p>
    <p><strong>Due Date:</strong> {{ $task->due_date }}</p>
    <a href="{{ url('/tasks/' . $task->id) }}"
        style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View
        Task</a>
    <p>Thank you for your dedication to the project!</p>
@endsection
