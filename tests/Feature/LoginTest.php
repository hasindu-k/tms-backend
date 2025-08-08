<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class LoginTest extends TestCase
{

    use RefreshDatabase;

    private const LOGIN_ROUTE = '/api/auth/login';

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $loginData = [
            'email'    => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson(self::LOGIN_ROUTE, $loginData);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'user' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ],
            'authorization' => [
                'access_token',
                'expires_in',
                'token_type',
            ],
        ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $loginData = [
            'email'    => 'invalid@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson(self::LOGIN_ROUTE, $loginData);
        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Incorrect email or password',
        ]);
    }

    public function test_user_cannot_login_due_to_token_creation_failure()
    {
        User::factory()->create([
            'email' => 'user1@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Mock the JWTAuth::attempt method to throw a JWTException
        JWTAuth::shouldReceive('attempt')
            ->once()
            ->andThrow(new JWTException('Could not create token'));

        $loginData = [
            'email'    => 'user1@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson(self::LOGIN_ROUTE, $loginData);
        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'Could not create token',
            'status' => 500,
        ]);
    }

    public function test_user_cannot_login_with_missing_email_or_password()
    {
        $loginData = [
            'password' => 'password123',
        ];

        $response = $this->postJson(self::LOGIN_ROUTE, $loginData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_login_with_missing_password()
    {
        $loginData = [
            'email' => 'user@example.com',
        ];

        $response = $this->postJson(self::LOGIN_ROUTE, $loginData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }
}
