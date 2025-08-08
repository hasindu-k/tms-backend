@extends('emails.layout')

@section('content')
    <h1>New Comment on Task: {{ $task->title }}</h1>
    <p>A new comment has been added to the task.</p>
    <p><strong>Comment:</strong> {{ $comment->content }}</p>

    <a href="{{ url('/tasks/' . $task->id) }}"
        style="padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none;">
        View Task
    </a>

    <p>Thank you for using our application!</p>
@endsection
