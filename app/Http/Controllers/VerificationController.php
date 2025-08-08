<?php

namespace App\Http\Controllers;

use App\Services\EmailVerificationService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    protected $emailVerificationService;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        $result = $this->emailVerificationService->verifyEmail($request);

        return response()->json([
            'message' => $result['message']
        ], $result['status']);
    }

    public function resend(Request $request): JsonResponse
    {
        $result = $this->emailVerificationService->resendVerificationEmail($request);

        return response()->json([
            'message' => $result['message']
        ], $result['status']);
    }
}
