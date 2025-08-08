<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Requests\ProjectUserAssignmentRequest as UserAssignmentRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            $projects = Project::where('created_by', $user->id)->paginate(10);

            return ProjectResource::collection($projects);

        } catch (Exception $e) {
            $response = ['message' => 'Failed to retrieve projects: ' . $e->getMessage()];
            $status = 500;
            return response()->json($response, $status);
        }
    }

    public function getUserProjects()
    {
        try {
            $user = Auth::user();

            $projects = $user->projects;

            if ($projects->isEmpty()) {
                return response()->json([
                    'message' => 'No projects assigned to this user.',
                ], 404);
            }

            return ProjectResource::collection($projects);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function store(StoreProjectRequest $request)
    {
        $response = ['message' => 'Failed to create project.'];
        $status = 500;

        try {
            $validatedData = $request->validated();
            $project = Project::create([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'created_by' => Auth::id(),
            ]);

            $user = JwtAuth::user();
            if ($user->hasRole('user')) {
                $user->assignRole('manager');
            }

            $response = new ProjectResource($project);
            $status = 200;

        } catch (ValidationException $e) {
            $response = ['message' => 'Validation failed', 'errors' => $e->errors()];
            $status = 422;

        } catch (QueryException $e) {
            $response = ['message' => 'Database query error: ' . $e->getMessage()];
            $status = 500;

        } catch (Exception $e) {
            $response = ['message' => 'Failed to create project: ' . $e->getMessage()];
            $status = 500;
        }
        return response()->json($response, $status);
    }



    public function show($project)
    {
        try {
            $project = Project::findOrFail($project);
            Gate::authorize('view', $project);
            return new ProjectResource($project);

        } catch (ModelNotFoundException $e) {
            $response = ['message' => 'Project not found.'];
            $status = 404;

        } catch (Exception $e) {
            $response = ['message' => 'Failed to show project : ' . $e->getMessage()];
            $status = 500;
        }
        return response()->json($response, $status);
    }

    public function update(UpdateProjectRequest $request, $project)
    {
        try {
            $project = Project::findOrFail($project);
            Gate::authorize('view', $project);
            $project->update($request->validated());
            return new ProjectResource($project);

        } catch (ModelNotFoundException $e) {
            $response = ['message' => 'Project not found.'];
            $status = 404;

        } catch (Exception $e) {
            $response = ['message' => 'Failed to update Project: ' . $e->getMessage()];
            $status = 500;
        }
        return response()->json($response, $status);
    }

    public function destroy($project)
    {
        try {
            $project = Project::findOrFail($project);
            Gate::authorize('view', $project);
            $project->delete();
            $response = ['message' => 'Project deleted successfully'];
            $status = 200;

        } catch (ModelNotFoundException $e) {
            $response = ['message' => 'Project not found.'];
            $status = 404;

        } catch (Exception $e) {
            $response = ['message' => 'Failed to delete Project: ' . $e->getMessage()];
            $status = 500;
        }

        return response()->json($response, $status);
    }
    public function getProjectUsers($projectId): JsonResponse
    {
        try {
            $project = Project::findOrFail($projectId);
            Gate::authorize('view', $project);

            // Use distinct() to avoid duplicate users
            $users = $project->users()->distinct()->get();

            return response()->json([
                'message' => 'Project users retrieved successfully.',
                'users' => UserResource::collection($users),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Project not found.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized to access this project.'], 403);
        } catch (Exception $e) {
            Log::error('Unexpected error: ' . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred.'], 400);
        }

    }

    public function assignUser(UserAssignmentRequest $request, $projectId): JsonResponse
    {
        try {
            $project = Project::findOrFail($projectId);
            Gate::authorize('isProjectManager', $project);

            $validatedData = $request->validated();
            $userIds = $validatedData['user_ids'];

            $alreadyaddedUserIds = $project->users()->pluck('id')->toArray();
            $previousAddedUsers = $project->users()->whereIn('id', $alreadyaddedUserIds)->get();

            $notAddedUserIds = array_diff($userIds, $alreadyaddedUserIds);

            if (empty($notAddedUserIds)) {
                return response()->json([
                    'message' => 'User already added to project.',
                    'already assigned users' => UserResource::collection($previousAddedUsers),
                ], 200);
            }

            $project->users()->attach($notAddedUserIds);
            $newUsers = $project->users()->whereIn('id', $notAddedUserIds)->get();

            return response()->json([
                'message' => 'User assigned to project successfully.',
                'new users' => UserResource::collection($newUsers),
                'already assigned users' => UserResource::collection($previousAddedUsers),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Project or user not found.'], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'Unauthorized to access this project.'], 403);
        } catch (Exception $e) {
            Log::error('Unexpected error: ' . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred.'], 400);
        }
    }
}

