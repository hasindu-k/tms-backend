<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::get('/google', [GoogleController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [GoogleController::class, 'handleGoogleCallback']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('guest')
        ->name('password.email');

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('guest')
        ->name('password.update');

    Route::get('/reset-password/{token}', [AuthController::class, 'handleResetPassword'])
        ->middleware('guest')
        ->name('password.reset');
});

// Email Verification Routes
Route::prefix('email')->middleware(['auth:api'])->group(function () {
    Route::get('/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Authenticated and Verified Routes
Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::apiResource('/tasks', TaskController::class);
    Route::apiResource('/comments', CommentController::class);
    Route::post('/update/avatar', [AuthController::class, 'uploadAvatar']);

    Route::prefix('users')->group(function () {
        Route::post('/show/dashboard/{project}', [UserDashboardController::class, 'getUsersProjectTasks']);
        Route::get('/projects', [ProjectController::class, 'getUserProjects']);


        // Task Routes
        Route::prefix('tasks')->group(function () {
            Route::patch('/edit/status/{task}', [UserDashboardController::class, 'editTaskStatus']);
            Route::post('/assign/manager/{task}', [UserDashboardController::class, 'assignBackTaskToManager']);
            Route::get('/show/{task}', [UserDashboardController::class, 'assignTaskShow']);
            Route::post('log-time/{task}', [UserDashboardController::class, 'logTime']);
            Route::post('/created/{projectId}', [DashboardController::class, 'getManagerCreatedTasks']);
        });

        // Comment Routes
        Route::prefix('comments')->group(function () {
            Route::get('/{task}', [TaskController::class, 'getComments']);
            Route::post('/create/{task}', [UserDashboardController::class, 'addComments']);
            Route::patch('/update/{comment}', [UserDashboardController::class, 'updateComment']);
            Route::delete('/delete/{comment}', [UserDashboardController::class, 'deleteComment']);
            Route::get('/show/{comment}', [UserDashboardController::class, 'showComment']);
        });
    });


    Route::prefix('projects')->group(function () {
        Route::post('/create', [ProjectController::class, 'store']);
        Route::get('/index', [ProjectController::class, 'index']);
        Route::get('show/{project}', [ProjectController::class, 'show']);
        Route::get('users/{project}', [ProjectController::class, 'getProjectUsers']);
    });


    Route::middleware([CheckRole::class . ':manager'])->prefix('dashboard/managers')->group(function () {
        // Task Routes
        Route::prefix('tasks')->group(function () {
            Route::post('/assigned/{projectId}', [DashboardController::class, 'getManagerAssignedTasks']);
            Route::post('create/{project}', [DashboardController::class, 'createTask']);
            Route::get('show/{task}', [DashboardController::class, 'createdTaskShow']);
            Route::patch('update/{task}', [DashboardController::class, 'updateTask']);
            Route::delete('delete/{task}', [DashboardController::class, 'deleteTask']);
            Route::post('assign/{task}', [DashboardController::class, 'assignTask']);
            Route::post('unassign/{task}', [DashboardController::class, 'unassignTask']);
            Route::patch('/edit/description/{task}', [DashboardController::class, 'editTaskDescription']);
            Route::patch('/edit/priority/{task}', [DashboardController::class, 'editTaskPriority']);
            Route::patch('/edit/estimated-time/{task}', [DashboardController::class, 'editTaskEstimatedTime']);
        });

        // Comment Routes
        Route::prefix('comments')->group(function () {
            Route::post('create/{task}', [DashboardController::class, 'addComment']);
            Route::get('show/{comment}', [DashboardController::class, 'showComment']);
            Route::patch('update/{comment}', [DashboardController::class, 'updateComment']);
            Route::delete('delete/{comment}', [DashboardController::class, 'deleteComment']);
        });

        Route::get('users', [DashboardController::class, 'showAllUsers']);
    });

    Route::middleware([CheckRole::class . ':manager'])->prefix('manager/projects')->group(function () {
        Route::patch('update/{project}', [ProjectController::class, 'update']);
        Route::delete('delete/{project}', [ProjectController::class, 'destroy']);
        Route::post('assign/{project}', [ProjectController::class, 'assignUser']);
    });
});
