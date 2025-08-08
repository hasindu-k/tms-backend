<?php

namespace App\Services;

use App\Http\Controllers\CommentController;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use App\Notifications\CommentAddedNotification;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CommentService
{
    protected $commentController;

    public function __construct(CommentController $commentController)
    {
        $this->commentController = $commentController;
    }

    public function addCommentToTask($request, $taskId, $gateAction)
    {
        $response = [];
        $status = 200;

        try {
            $task = Task::findOrFail($taskId);
            Gate::authorize($gateAction, $task);

            $comment = $this->commentController->store($request, $taskId);

            if ($comment instanceof Comment) {

                $response = [
                    'data' => new CommentResource($comment),
                    'message' => 'Comment added successfully.'
                ];
                try {
                    // Notify all users associated with the task
                    foreach ($task->users as $user) {
                        $user->notify(new CommentAddedNotification($task, $comment));
                    }
                } catch (Exception $e) {
                    Log::error('Failed to send notifications: ' . $e->getMessage());
                    $response = [
                        'message' => 'Comment Added, but failed to send notifications.',
                    ];
                    $status = 500;
                    return compact('response', 'status');
                }
            }
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (ModelNotFoundException $e) {
            $response = [
                'message' => 'Task not found.',
            ];
            $status = 404;
        } catch (Exception $e) {
            $response = [
                'message' => 'Failed to create comment: ' . $e->getMessage(),
            ];
            $status = 500;
        }

        return compact('response', 'status');
    }

    public function updateComment($request, $commentId, $gateAction)
    {
        $response = [];
        $status = 200;

        try {
            $comment = Comment::findOrFail($commentId);
            Gate::authorize($gateAction, $comment);

            $updatedComment = $this->commentController->update($request, $comment);

            $response = [
                'message' => 'Comment updated successfully.',
                'comment' => new CommentResource($updatedComment),
                'check' => Auth::user()->id,
            ];
        } catch (ModelNotFoundException $e) {
            $response = ['message' => 'Comment not found.'];
            $status = 404;
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (Exception $e) {
            $response = ['message' => 'Failed to update comment: ' . $e->getMessage()];
            $status = 500;
        }

        return compact('response', 'status');
    }

    public function deleteComment($commentId, $gateAction)
    {
        $response = [];
        $status = 200;

        try {
            $comment = Comment::findOrFail($commentId);
            Gate::authorize($gateAction, $comment);

            $this->commentController->destroy($comment);

            $response = ['message' => 'Comment deleted successfully.'];
        } catch (ModelNotFoundException $e) {
            $response = ['message' => 'Comment not found.'];
            $status = 404;
        } catch (Exception $e) {
            $response = ['message' => 'Failed to delete comment: ' . $e->getMessage()];
            $status = 500;
        }

        return compact('response', 'status');
    }
}
