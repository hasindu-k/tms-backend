<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class GoogleService
{
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(24)),
                ]);
            }

            $needsSave = false;

            if (!$user->avatar && $googleUser->avatar) {
                $user->avatar = $googleUser->avatar;
                $needsSave = true;
            }

            if (!$user->google_id) {
                $user->google_id = $googleUser->id;
                $needsSave = true;
            }

            if ($needsSave) {
                $user->save();
            }

            $token = JWTAuth::fromUser($user);

            return redirect()->to(
                config('app.frontend_url') .
                    "/auth/google/callback?token={$token}"
            );
        } catch (\Throwable $e) {
            Log::error('Google OAuth failed', [
                'error' => $e->getMessage()
            ]);

            return redirect()->to(
                config('app.frontend_url') .
                    "/auth/google/callback?error=google_auth_failed"
            );
        }
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }
}
