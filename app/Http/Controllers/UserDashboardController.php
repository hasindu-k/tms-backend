<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignBackTaskRequest;
use App\Http\Requests\LogTimeRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\TaskResource;
use App\Models\Comment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use App\Notifications\TaskStatusChangedNotification;
use App\Services\CommentService;
use App\Services\TaskService;
use App\Services\TimeLogService;
use App\Traits\CommentTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserDashboardController extends Controller
{
    use CommentTrait;
    protected $taskService;
    protected $commentController;
    protected $commentService;
    protected $timeLogService;

    public function __construct(TaskService $taskService, CommentController $commentController, CommentService $commentService, TimeLogService $timeLogService)
    {
        $this->taskService = $taskService;
        $this->commentController = $commentController;
        $this->commentService = $commentService;
        $this->timeLogService = $timeLogService;
    }
    protected function getUserTasks($request, $type, $projectId)
    {
        $user = JWTAuth::user();
        $filters = $request->only(['status', 'priority', 'date_range', 'date_field']);
        $filters['project_id'] = $projectId;

        // Get tasks assigned to the user
        $tasksQuery = $this->taskService->filterTasks($filters, $user, $type);

        // Apply sorting
        $sortRequest = $request->only(['sort_by', 'sort_order']);
        $tasksQuery = $this->taskService->sortTasks($tasksQuery, $sortRequest);

        // Paginate results
        $tasks = $tasksQuery->paginate(10);

        if ($tasks->isEmpty()) {
            return response()->json([
                'grouped_tasks' => [],
                'message' => $type === 'assigned'
                    ? 'No tasks have been assigned to this user.'
                    : 'No tasks have been created by this user.',
            ], 200);
        }

        // Group tasks by status
        $groupedTasks = $tasks->groupBy('status');

        // Transform grouped tasks for API response
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

    public function getUsersProjectTasks(Request $request, $projectId)
    {
        return $this->getUserTasks($request, 'assigned', $projectId);
    }

    public function assignTaskShow($taskid)
    {
        $task = Task::findOrFail($taskid);
        Gate::authorize('viewAssignTask', $task);
        return new TaskResource($task);
    }
    public function editTaskStatus(Task $task, UpdateStatusRequest $request): JsonResponse
    {
        try {
            $result = $this->taskService->updateTaskStatus($task, $request->validated());
            return response()->json($result['response'], $result['status']);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 400);
        }

    }
    public function assignBackTaskToManager(Task $task, AssignBackTaskRequest $request)
    {
        $validatedData = $request->validated();
        $result = $this->taskService->assignBackTaskToManager($task, $validatedData);

        return response()->json($result['response'], $result['status']);
    }
    public function logTime(LogTimeRequest $request, $task)
    {
        try {
            $task = Task::findOrFail($task);
            Gate::authorize('update', $task);
            $result = $this->timeLogService->logTime($request->validated(), $task);
            return response()->json($result['response'], $result['status']);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 400);

        }
    }
    public function addComments(StoreCommentRequest $request, $taskId)
    {
        $result = $this->commentService->addCommentToTask($request, $taskId, 'update');
        return response()->json($result['response'], $result['status']);
    }

    public function updateComment(UpdateCommentRequest $request, $commentId)
    {
        $result = $this->commentService->updateComment($request, $commentId, 'delete');
        return response()->json($result['response'], $result['status']);
    }

    public function deleteComment($commentId)
    {
        $result = $this->commentService->deleteComment($commentId, 'delete');
        return response()->json($result['response'], $result['status']);
    }

    public function show($comment)
    {
        return $this->showComment($comment);
    }

}
