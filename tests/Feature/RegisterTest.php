<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class RegisterTest extends TestCase
{

    use RefreshDatabase;

    private const REGISTER_ROUTE = '/api/auth/register';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed RolesAndPermissionsSeeder');
    }

    public function test_user_can_register_successfully(): void
    {
        $userData = [
            'name' => 'Testing User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(self::REGISTER_ROUTE, $userData);
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => [
                'id',
                'name',
                'email',
            ],
            'token',
        ]);
    }

    public function test_user_cannot_register_with_missing_data(): void
    {
        $userData = [
            'name' => 'Testing User',
        ];

        $response = $this->postJson(self::REGISTER_ROUTE, $userData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $userData = [
            'name' => 'Test User',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(self::REGISTER_ROUTE, $userData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_server_error(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'erroruser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $userRepository = Mockery::mock(UserRepository::class);
        $userRepository->shouldReceive('createUser')
            ->once()
            ->andThrow(new \Exception('Test Exception'));

        $this->app->instance(UserRepository::class, $userRepository);
        $response = $this->postJson(self::REGISTER_ROUTE, $userData);
        $response->assertStatus(500);

        $response->assertJson([
            'error' => 'An error occurred during registration. Please try again.',
        ]);
    }
}
