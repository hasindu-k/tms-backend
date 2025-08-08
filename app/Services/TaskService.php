<?php

namespace App\Services;

use App\Http\Requests\UpdateStatusRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskStatusChangedNotification;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TaskService
{

    public function filterTasks($filters, $user, $taskType = null)
    {
        $query = Task::query();

        // Filter by project ID if provided
        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        // Filter by task status if provided
        if (isset($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        // Filter by priority if provided
        if (isset($filters['priority'])) {
            $query->whereIn('priority', (array) $filters['priority']);
        }

        // Filter by date range if both `date_range` and `date_field` are provided
        if (isset($filters['date_range']) && isset($filters['date_field'])) {
            $this->applyDateRangeFilter($query, $filters['date_range'], $filters['date_field']);
        }

        // Manager-specific task filters
        if ($user->hasRole('manager')) {
            $managerId = $user->id;
            if ($taskType === 'created') {
                $query->where('created_by', $managerId);
            } elseif ($taskType === 'assigned') {
                $query->whereHas('users', function ($subQuery) use ($managerId) {
                    $subQuery->where('task_user.assigned_by', $managerId);
                });
            } else {
                $query->where('created_by', $managerId)
                    ->orWhereHas('users', function ($subQuery) use ($managerId) {
                        $subQuery->where('task_user.assigned_by', $managerId);
                    });
            }
        }
        else {
            // filter tasks assigned to logged user
            if($taskType === 'assigned') {
                $query->whereHas('users', function ($subQuery) use ($user) {
                    $subQuery->where('task_user.user_id', $user->id);
                });
            }
        }
        return $query;
    }

    protected function applyDateRangeFilter($query, $dateRange, $dateField)
    {
        $now = now();

        switch ($dateRange) {
            case 'last_24_hours':
                $query->whereBetween($dateField, [$now->copy()->subDay(), $now]);
                break;
            case 'last_7_days':
                $query->whereBetween($dateField, [$now->copy()->subDays(7), $now]);
                break;
            case 'last_14_days':
                $query->whereBetween($dateField, [$now->copy()->subDays(14), $now]);
                break;
            case 'last_month':
                $query->whereBetween($dateField, [$now->copy()->subMonth(), $now]);
                break;
            default:
                break;
        }

        return $query;
    }
    public function sortTasks($query, $request)
    {
        $sortField = $request['sort_by'] ?? 'created_at';
        $sortDirection = $request['sort_order'] ?? 'asc';

        $sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc';

        return $query->orderBy($sortField, $sortDirection)->with('users');
    }

    public function assignUsers($taskId, $userIds)
    {
        $assignUsers = [];

        foreach ($userIds as $userId) {
            DB::table('task_user')->insert([
                'task_id' => $taskId,
                'user_id' => $userId,
                'assigned_by' => Auth::user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $assignUsers[] = $userId;
        }

        $users = User::whereIn('id', $assignUsers)->get();
        $task = Task::findOrFail($taskId);

        return [
            'task' => new TaskResource($task),
            'users' => UserResource::collection($users),
        ];
    }
    public function checkExistingAssignments(int $taskId, array $userIds): array
    {
        $task = Task::findOrFail($taskId);

        // Check provided user IDs are already assigned to any task
        $existingAssignments = $task->users()
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->toArray();

        // Always return 'already_assigned_users' key, even is empty
        return [
            'status' => !empty($existingAssignments) ? 400 : 200,
            'message' => !empty($existingAssignments)
                ? 'Some users are already assigned to this task.'
                : 'No users already assigned to this task.',
            'already_assigned_users' => $existingAssignments, // Always include this key
        ];
    }
    public function unassignTask(int $taskId, $userIds): array
    {
        // Check if any assignments for the given task ID
        $existingAssignments = DB::table('task_user')
            ->where('task_id', $taskId)
            ->count();

        if ($existingAssignments === 0) {
            return [
                'status' => 'error',
                'message' => 'No users are assigned to this task.',
                'code' => 404
            ];
        }

        Log::info('Unassigning users from the task.', [
            'task_id' => $taskId,
            'user_ids' => $userIds,
        ]);

        //delete all assignments for the given task ID and user IDs
        $deletedRows = DB::table('task_user')
            ->where('task_id', $taskId)
            ->whereIn('user_id', $userIds)
            ->delete();

        if ($deletedRows > 0) {
            return [
                'status' => 'success',
                'message' => 'Unassigned from the task successfully.',
                'code' => 200
            ];
        } else {
            throw new Exception('Failed to unassign users.', 500);
        }
    }
    public function assignBackTaskToManager(Task $task, array $validatedData)
    {
        $response = [];
        $status = 200;

        try {
            Gate::authorize('assignBack', $task);

            $originalManagerId = $validatedData['manager_id'];

            if (Auth::id() == $task->assigned_manager_id) {
                $response = ['message' => 'You have already assigned this task back to yourself.'];
                $status = 403;
            } elseif ($originalManagerId != $task->created_by) {
                $response = ['message' => 'You can only assign this task back to the original manager.'];
                $status = 403;
            } else {
                $task->assigned_manager_id = Auth::id();
                $task->save();

                $response = [
                    'message' => 'Task successfully assigned back to the manager.',
                    'task' => $task,
                    'assigned_to_manager' => Auth::id(),
                ];
            }
        } catch (ModelNotFoundException $e) {
            $response = ['message' => 'Task not found.'];
            $status = 404;
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (Exception $e) {
            $response = ['message' => $e->getMessage()];
            $status = 400;
        }

        return [
            'response' => $response,
            'status' => $status,
        ];
    }
    public function updateTaskStatus(Task $task, array $validatedData): array
    {
        $response = [];
        $status = 200;

        try {
            Gate::authorize('update', $task);

            $statusUpdateSuccess = $task->update([
                'status' => $validatedData['status'],
            ]);

            if ($statusUpdateSuccess) {
                $response = [
                    'data' => $task,
                    'message' => 'Task status updated successfully.',
                ];

                $currentUser = JWTAuth::user();

                if ($currentUser->hasRole('user') && $task->assigned_manager_id) {
                    $manager = User::find($task->assigned_manager_id);
                    if ($manager) {
                        $manager->notify(new TaskStatusChangedNotification($task));
                    } else {
                        throw new Exception('Failed to find manager to send email.');
                    }
                }
            } else {
                throw new Exception('Failed to update task status.');
            }
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (Exception $e) {
            $response = [
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ];
            $status = 400;
        }
        return ['response' => $response, 'status' => $status];
    }
    public function updateTaskDescription(Task $task, array $validatedData): array
    {
        $response = [];
        $status = 200;

        try {
            Gate::authorize('update', $task);

            // Update the task description
            $descriptionUpdateSuccess = $task->update([
                'description' => $validatedData['description'],
            ]);

            if ($descriptionUpdateSuccess) {
                $response = [
                    'id' => $task->id,
                    'description' => $task->description,
                    'message' => 'Task description updated successfully.',
                ];
            } else {
                throw new Exception('Failed to update task description.');
            }
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (Exception $e) {
            $response = [
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ];
            $status = 400;
        }
        return ['response' => $response, 'status' => $status];
    }

    public function updateTaskPriority(Task $task, array $validatedData): array
    {
        $response = [];
        $status = 200;

        try {
            Gate::authorize('update', $task);

            $validPriorities = [1, 2, 3];
            if (!in_array($validatedData['priority'], $validPriorities)) {
                throw new Exception('Invalid priority value provided.');
            }

            $priorityUpdateSuccess = $task->update([
                'priority' => $validatedData['priority'],
            ]);

            if ($priorityUpdateSuccess) {
                $response = [
                    'id' => $task->id,
                    'priority' => $task->priority,
                    'message' => 'Task priority updated successfully.',
                ];
            } else {
                throw new Exception('Failed to update task priority.');
            }
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (Exception $e) {
            $response = [
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ];
            $status = 400;
        }
        return ['response' => $response, 'status' => $status];
    }

    public function updateTaskEstimatedTime(Task $task, array $validatedData): array
    {
        $response = [];
        $status = 200;

        try {
            Gate::authorize('update', $task);

            $timeUpdateSuccess = $task->update([
                'estimated_time' => $validatedData['estimated_time'],
            ]);

            if ($timeUpdateSuccess) {
                $response = [
                    'id' => $task->id,
                    'estimated_time' => $task->estimated_time,
                    'message' => 'Task estimated time updated successfully.',
                ];
            } else {
                throw new Exception('Failed to update task estimated time.');
            }
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (Exception $e) {
            $response = [
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ];
            $status = 400;
        }
        return ['response' => $response, 'status' => $status];
    }
}
