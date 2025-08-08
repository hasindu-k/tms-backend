<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TaskResource::collection(Task::paginate(10));
    }
    public function getComments(Task $task)
    {
        try {
            $comments = $task->comments()->paginate(10);

            return CommentResource::collection($comments);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Task or comments not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while retrieving comments.'], 500);
        }
    }

    public function store(StoreTaskRequest $request)
    {
        $validatedData = $request->validated();
        $task = Task::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'status' => $validatedData['status'],
            'priority' => $validatedData['priority'],
            'created_by' => Auth::user()->id
        ]);
        return new TaskResource($task);
    }


    public function show(Task $task)
    {
        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());
        return new TaskResource($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()
            ->json(['message' => 'Task deleted successfully'], 200);
    }
}
