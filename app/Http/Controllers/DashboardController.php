<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Requests\UpdateDescriptionRequest;
use App\Http\Requests\UpdateEstimatedTimeRequest;
use App\Http\Requests\UpdatePriorityRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UserIdsRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Services\CommentService;
use App\Services\TaskService;
use App\Traits\CommentTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class DashboardController extends Controller
{
    use CommentTrait;
    protected $taskController;
    protected $commentController;
    protected $taskService;
    protected $commentService;
    public function __construct(TaskController $taskController, TaskService $taskService, CommentController $commentController, CommentService $commentService)
    {
        $this->taskController = $taskController;
        $this->taskService = $taskService;
        $this->commentController = $commentController;
        $this->commentService = $commentService;
    }
    public function assignTask(UserIdsRequest $request, $taskId): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $userIds = $validatedData['user_ids'];
            $task = Task::findOrFail($taskId);
            $managerId = Auth::user()->id;

            Gate::authorize('isCreatedBy', $task);

            $assignmentCheck = $this->taskService
                ->checkExistingAssignments($taskId, $userIds);

            $alreadyAssignedUsers = $assignmentCheck['already_assigned_users'] ?? [];
            $newUserIds = array_diff($userIds, $alreadyAssignedUsers);

            $newUsers = User::whereIn('id', $newUserIds)->get();

            if ($newUsers->isNotEmpty()) {
                try {
                    Notification::send($newUsers, new TaskAssigned($task));
                } catch (Exception $e) {
                    Log::error('Failed to send notifications: ' . $e->getMessage());
                    $response = [
                        'message' => 'Task assigned, but failed to send notifications.',
                    ];
                    $status = 500;
                    return response()->json($response, $status);
                }
            }

            if (!empty($newUserIds)) {
                $result = $this->taskService->assignUsers($taskId, $newUserIds);
                $task->assigned_manager_id = $managerId;
                $task->save();

                // Attach users to the project
                $project = $task->project;
                $project->attachUsers($newUserIds);

                $response = [
                    'message' => 'Task assigned successfully to new users.',
                    'task' => $result['task'],
                    'assigned_users' => $result['users'],
                    'already_assigned_users' => $alreadyAssignedUsers,
                ];
                $status = 200;
            } else {
                $response = [
                    'message' => 'No new users were assigned. All provided users are already assigned to this task.',
                    'already_assigned_users' => $alreadyAssignedUsers,
                ];
                $status = 200;
            }
        } catch (ModelNotFoundException $e) {
            $response = [
                'message' => 'Task not found.',
            ];
            $status = 404;
        } catch (ValidationException $e) {
            Log::error('Validation error: ' . $e->getMessage());
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (Exception $e) {
            Log::error('Unexpected error: ' . $e->getMessage());
            $response = [
                'message' => 'An unexpected error occurred.',
            ];
            $status = 400;
        }

        return response()->json($response, $status);
    }

    public function unassignTask(UserIdsRequest $request, $taskId): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $userIds = $validatedData['user_ids'];
            $task = Task::findOrFail($taskId);
            Gate::authorize('isAssignManager', $task);
            $result = $this->taskService->unassignTask($taskId, $userIds);

            $response = [
                'message' => $result['message']
            ];
            $status = $result['code'];
        } catch (ModelNotFoundException $e) {
            $response = [
                'message' => 'Task not found.',
            ];
            $status = 404;
        } catch (Exception $e) {
            $response = [
                'message' => $e->getMessage()
            ];
            $status = 400;
        }
        return response()->json($response, $status);
    }
    public function createTask(StoreTaskRequest $request, $project)
    {
        try {
            $project = Project::findOrFail($project);
            Gate::authorize('view', $project);

            $taskData = $request->validated();
            $taskData['project_id'] = $project->id;
            $taskData['created_by'] = Auth::id();

            $task = Task::create($taskData);

            return new TaskResource($task);
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (Exception $e) {
            $response = [
                'message' => $e->getMessage(),
            ];
            $status = 400;
        }
        return response()->json($response, $status);
    }
    public function createdTaskShow($taskid)
    {
        $task = Task::findOrFail($taskid);
        Gate::authorize('viewBy', $task);
        return new TaskResource($task);
    }
    protected function getAllTasks(Request $request, $type, $projectId)
    {
        $manager = JWTAuth::user();
        $filters = $request->only(['status', 'priority', 'date_range', 'date_field']);

        $filters['project_id'] = $projectId;

        $tasksQuery = $this->taskService->filterTasks($filters, $manager, $type);

        $sortRequest = $request->only(['sort_by', 'sort_order']);
        $tasksQuery = $this->taskService->sortTasks($tasksQuery, $sortRequest);

        $tasks = $tasksQuery->paginate(10);

        if ($tasks->isEmpty()) {
            return response()->json([
                'grouped_tasks' => [],
                'message' => $type === 'assigned'
                    ? 'No tasks have been assigned by this manager.'
                    : 'No tasks have been created by this manager.',
            ], 200);
        }

        $groupedTasks = $tasks->groupBy('status');

        $groupedTasksTransformed = $groupedTasks->map(function ($tasks, $status) {
            return [
                'status' => $status,
                'tasks' => TaskResource::collection($tasks),
            ];
        });

        return response()->json([
            'grouped_tasks' => $groupedTasksTransformed,
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'total' => $tasks->total(),
                'per_page' => $tasks->perPage(),
                'next_page_url' => $tasks->nextPageUrl(),
                'prev_page_url' => $tasks->previousPageUrl(),
            ],
        ]);
    }

    public function getManagerAssignedTasks(Request $request, $projectId)
    {
        return $this->getAllTasks($request, 'assigned', $projectId);
    }
    public function getManagerCreatedTasks(Request $request, $projectId)
    {
        return $this->getAllTasks($request, 'created', $projectId);
    }
    public function updateTask(UpdateTaskRequest $request, Task $task)
    {
        try {
            Gate::authorize('isCreatedBy', $task);
            $updateTask = $this->taskController->update($request, $task);
            $response = [
                'message' => 'Task updated successfully.',
                'task' => new TaskResource($updateTask),
            ];
            $status = 200;
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
            $response = [
                'message' => $e->getMessage(),
            ];
            $status = 500;
        }
        return response()->json($response, $status);
    }

    public function deleteTask($taskId)
    {
        try {
            $task = Task::findOrFail($taskId);
            Gate::authorize('isCreatedBy', $task);
            $this->taskController->destroy($task);

            $response = ['message' => 'Task deleted successfully'];
            $status = 200;
        } catch (ModelNotFoundException $e) {
            $response = ['message' => 'Task not found.'];
            $status = 404;
        } catch (Exception $e) {
            $response = ['message' => 'Failed to delete task: ' . $e->getMessage()];
            $status = 500;
        }
        return response()->json($response, $status);
    }

    public function editTaskDescription(Task $task, UpdateDescriptionRequest $request): JsonResponse
    {
        try {
            $result = $this->taskService->updateTaskDescription($task, $request->validated());
            return response()->json($result['response'], $result['status']);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function editTaskPriority(Task $task, UpdatePriorityRequest $request): JsonResponse
    {
        try {
            $result = $this->taskService->updateTaskPriority($task, $request->validated());
            return response()->json($result['response'], $result['status']);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    public function editTaskEstimatedTime(Task $task, UpdateEstimatedTimeRequest $request): JsonResponse
    {
        try {
            $result = $this->taskService->updateTaskEstimatedTime($task, $request->validated());

            return response()->json($result['response'], $result['status']);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function addComment(StoreCommentRequest $request, $taskId)
    {
        $result = $this->commentService->addCommentToTask($request, $taskId, 'isAssignManager');
        return response()->json($result['response'], $result['status']);
    }

    public function updateComment(UpdateCommentRequest $request, $commentId)
    {
        $result = $this->commentService->updateComment($request, $commentId, 'update');
        return response()->json($result['response'], $result['status']);
    }

    public function deleteComment($commentId)
    {
        $result = $this->commentService->deleteComment($commentId, 'canDeleteComment');
        return response()->json($result['response'], $result['status']);
    }
    public function show($comment)
    {
        return $this->showComment($comment);
    }
    public function showAllUsers()
    {
        $users = User::whereNotNull('email_verified_at')->paginate(10);
        return UserResource::collection($users);
    }
}
