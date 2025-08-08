<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;

class CommentPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view any models.
     */
    public function isCreatedBy(User $user, Comment $comment)
    {
        return $user->id === $comment->user_id;

    }

    public function viewAny(User $user, Comment $comment)
    {
        $comment->load('task.project.users');

        if (!$comment->task || !$comment->task->project) {
            return false;
        }
        // Check if the user is part of the project that the task belongs to
        return $comment->task->project->users->contains($user);
    }


    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return Auth::user()->id === $comment->user_id;
    }
    public function canDeleteComment(User $user, Comment $comment): bool
    {
        $task = $comment->task;
        return $user->id === $comment->user_id || $task->assigned_manager_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comment $comment): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        //
    }
}
