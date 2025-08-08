<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Tests\TestCase;
use Illuminate\Http\Response;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private const LOGOUT_ROUTE = '/api/logout';

    public function test_user_can_logout_successfully()
    {
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andReturnSelf();

        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->andReturn(true);

        $user = User::factory()->create([
            'email' => 'testing@example.com',
        ]);

        $this->actingAs($user, 'api');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer fake_jwt_token',
        ])->postJson(self::LOGOUT_ROUTE);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'User Successfully Logged Out'
            ]);
    }

    public function test_user_cannot_logout_with_invalid_token()
    {
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andThrow(JWTException::class);

        $user = User::factory()->create([
            'email' => 'test@gmail.com',
        ]);

        $this->actingAs($user, 'api');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
        ])->postJson(self::LOGOUT_ROUTE);

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson([
                'error' => 'User can not Logged Out',
            ]);

    }

    public function test_user_cannot_logout_without_token()
    {
        $response = $this->postJson(self::LOGOUT_ROUTE);
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
