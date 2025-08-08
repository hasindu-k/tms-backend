<?php

namespace App\Services;

use App\Notifications\UserWelcomeNotification;
use Exception;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function verifyEmail(EmailVerificationRequest $request): array
    {
        $user = $request->user();
        $response = [
            'status' => 400,
            'message' => 'Email verification failed',
        ];

        if ($user->hasVerifiedEmail()) {
            $response['status'] = 409;
            $response['message'] = 'Email is already verified';
            return $response;
        }

        $request->fulfill();

        if ($user->hasVerifiedEmail()) {
            try {
                $user->notify(new UserWelcomeNotification());
                $response['status'] = 200;
                $response['message'] = 'Email verified successfully';
            } catch (Exception $e) {
                Log::error('Failed to send notifications: ' . $e->getMessage());
                $response['status'] = 200;
                $response['message'] = 'User Registered, but failed to send notifications.';
            }
        }

        return $response;
    }

    public function resendVerificationEmail(Request $request): array
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return [
                'status' => 200,
                'message' => 'Email is already verified.',
            ];
        }

        $user->sendEmailVerificationNotification();

        return [
            'status' => 200,
            'message' => 'Verification link sent.',
        ];
    }
}
