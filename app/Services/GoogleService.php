<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class GoogleService
{
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if user exists
            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                $avatarUrl = $googleUser->avatar;

                // If avatar doesn't exist in Google profile, generate initials
                if (!$avatarUrl) {
                    $user->avatar = null; // No avatar available, will use fallback
                } elseif (!$user->avatar || !Storage::disk('public')->exists($user->avatar)) {
                    $avatarContents = file_get_contents($avatarUrl);
                    $avatarPath = 'avatars/' . uniqid() . '.jpg';
                    Storage::disk('public')->put($avatarPath, $avatarContents);

                    $user->avatar = $avatarPath;
                }

                $user->save();
                $token = JWTAuth::fromUser($user);
            } else {
                // Creating a new user if they don't exist
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar, // This might be null
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(24)),
                ]);

                $token = JWTAuth::fromUser($user);
            }

            $frontEndUrl = config('app.frontend_url');
            $path = $frontEndUrl . '/auth/google/callback?token=' . $token;
            return redirect()->to($path);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Unable to authenticate with Google',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }
}
