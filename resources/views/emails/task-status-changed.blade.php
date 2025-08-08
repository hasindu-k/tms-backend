@extends('emails.layout')

@section('content')
    <h1>Task Status Updated</h1>
    <p>The status of the task <strong>{{ $task->title }}</strong> has been updated to {{ $task->status }}.</p>
    <p>Updated by: {{ $updatedBy }}</p>
@endsection
