<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\UserRegisteredNotification;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Throwable;

class UserService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function loginUser(array $credentials)
    {
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()
                    ->json([
                        'error' => 'Incorrect email or password',
                        'status' => 401
                    ], 401);
            }
        } catch (JWTException $e) {
            return response()
                ->json([
                    'error' => 'Could not create token',
                    'status' => 500,
                ], 500);
        }

        $result = $this->respondWithToken($token);

        return response()
            ->json([
                'message' => 'User logged in successfully',
                'user' => new UserResource(Auth::user()),
                'authorization' => $result,
            ], 200);
    }

    public function logoutUser()
    {
        try {
            Log::info('Parsing token...');
            JWTAuth::parseToken()->invalidate();
            Log::info('Token invalidated.');
            return response()->json([
                'message' => 'User Successfully Logged Out'
            ], 200);
        } catch (JWTException $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'User can not Logged Out',
            ], 500);
        }
    }

    public function registerUser(array $userData)
    {
        try {
            $userData['password'] = Hash::make($userData['password']);
            $user = $this->userRepository->createUser($userData);
            $token = JWTAuth::fromUser($user);
            event(new Registered($user));

            $user->assignRole('user');

            $this->notifyManagers($user);

            return response()
                ->json([
                    'message' => 'User registered successfully. Please check your email to verify your account.',
                    'user' => $user,
                    'token' => $token,
                ], 201);
        } catch (Throwable $th) {
            return response()
                ->json([
                    'error' => 'An error occurred during registration. Please try again.',
                    'message' => $th->getMessage(),
                ], 500);
        }
    }

    public function me()
    {
        return new UserResource(Auth::user());
    }

    public function notifyManagers($user)
    {
        $managers = $this->userRepository->getManagers();
        try {
            Notification::send($managers, new UserRegisteredNotification($user));
        } catch (Exception $e) {
            Log::error('Failed to send notification: ' . $e->getMessage());
        }
        return $managers;
    }

    public function refreshToken()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();
            $result = $this->respondWithToken($newToken);
            return response()->json($result);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Refresh token is invalid or expired'
            ], 401);
        }
    }
    public function resetPassword(array $validatedData)
    {
        $status = Password::reset(
            [
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
                'token' => $validatedData['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successfully'], 200)
            : response()->json(['error' => 'Password reset failed'], 500);
    }

    public function handleResetPassword(string $token)
    {
        return response()->json(['token' => $token]);
    }

    public function forgotPassword(array $validatedData)
    {
        $status = Password::sendResetLink(
            ['email' => $validatedData['email']]
        );

        return $status === Password::RESET_LINK_SENT
            ? response()
            ->json(
                ['message' => 'Password reset link sent to your email'],
                200
            )
            : response()
            ->json(
                ['error' => 'Password reset link could not be sent'],
                500
            );
    }

    protected function respondWithToken($token)
    {
        $accessTime = JWTAuth::factory()->getTTL() * 60;
        return [
            'access_token' => $token,
            'expires_in' => $accessTime,
            'token_type' => 'bearer',
        ];
    }

    public function uploadAvatar($user, $avatarFile)
    {
        try {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $avatarFile->store('avatars', 'public');

            $user->avatar = $path;
            $user->save();

            return response()->json([
                'message' => 'Avatar uploaded successfully',
                'avatar_url' => Storage::url($path),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to upload avatar',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
