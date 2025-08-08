<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Services\UserService;
use App\Traits\HttpResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use HttpResponse;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(LoginUserRequest $request)
    {
        $validatedData = $request->validated();

        return $this->userService->loginUser($validatedData);
    }

    public function logout()
    {
        return $this->userService->logOutUser();
    }

    public function refresh()
    {
        return $this->userService->refreshToken();
    }

    public function register(RegisterUserRequest $request)
    {
        $validatedData = $request->validated();

        return $this->userService->registerUser($validatedData);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $validatedData = $request->validated();

        return $this->userService->resetPassword($validatedData);
    }

    public function handleResetPassword(Request $request)
    {
        $token = $request->token;

        return $this->userService->handleResetPassword($token);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $validatedData = $request->validated();

        return $this->userService->forgotPassword($validatedData);
    }

    public function me()
    {
        return $this->userService->me();
    }
    public function uploadAvatar(UploadAvatarRequest $request)
    {
        try {
            $user = Auth::user();
            return $this->userService->uploadAvatar($user, $request->file('avatar'));
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to upload avatar',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
