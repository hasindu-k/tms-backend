<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CommentResource::collection(Comment::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentRequest $request, $taskId)
    {
        $validatedData = $request->validated();

        $comment = new Comment([
            'task_id' => $taskId,
            'comment' => $validatedData['comment'],
            'user_id' => Auth::user()->id,
        ]);

        if ($comment->save()) {
            return $comment;
        } else {
            throw new Exception('Failed to create comment.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {

        Gate::authorize('view', $comment);
        return new CommentResource($comment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        //Gate::authorize('update', $comment);
        $comment->update($request->validated());
        return new CommentResource($comment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);
        $comment->delete();
        return response()
            ->json(['message' => 'Task deleted successfully'], 200);
    }
}
