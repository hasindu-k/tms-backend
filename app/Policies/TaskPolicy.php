<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    use HandlesAuthorization;

    public function isAssignManager(User $user, Task $task)
    {
        return $task->assigned_manager_id === $user->id || $user->id === $task->created_by;
    }
    public function isCreatedBy(User $user, Task $task)
    {
        return $task->created_by === $user->id;
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewBy(User $user, Task $task): bool
    {
        return $task->created_by === $user->id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function viewAssignTask(User $user, Task $task): bool
    {
        return $task->users->contains($user);
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
    public function update(User $user, Task $task): bool
    {
        return $task->users->contains($user) || $task->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        //
    }
    public function assignBack(User $user, Task $task): bool
    {
        return $task->users->contains($user);
    }
}
