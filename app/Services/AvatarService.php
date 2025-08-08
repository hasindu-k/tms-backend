<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;

class AvatarService
{
    public static function getAvatarUrl($user)
    {
        if ($user->avatar) {
            return config('app.url') . Storage::url($user->avatar);
        }

        return self::getAvatarFallback($user);
    }

    private static function getAvatarFallback($user)
    {
        $nameParts = explode(' ', $user->name);
        $initials = strtoupper($nameParts[0][0] . (isset($nameParts[1]) ? $nameParts[1][0] : ''));

        // Generate a random light color
        $lightColor = self::getRandomLightColor();
        return "https://ui-avatars.com/api/?name={$initials}&background={$lightColor}&color=000000";
    }
    private static function getRandomLightColor()
    {
        // Generate a random hex code for light colors
        $r = rand(200, 255); // Red value (light range)
        $g = rand(200, 255); // Green value (light range)
        $b = rand(200, 255); // Blue value (light range)

        // Convert RGB to hex
        return sprintf('%02X%02X%02X', $r, $g, $b);
    }
}
