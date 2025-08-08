<?php

namespace App\Http\Controllers;

use App\Services\GoogleService;

class GoogleController extends Controller
{
    protected $googleService;

    public function __construct(GoogleService $googleService)
    {
        $this->googleService = $googleService;
    }

    public function redirectToGoogle()
    {
        return $this->googleService->redirectToGoogle();
    }

    public function handleGoogleCallback()
    {
        return $this->googleService->handleGoogleCallback();
    }
}
