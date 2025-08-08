<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Task;
use App\Notifications\VerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_jwt_identifier()
    {
        $user = User::factory()->create();
        $this->assertEquals($user->getKey(), $user->getJWTIdentifier());
    }

    public function test__returns_jwt_custom_claims()
    {
        $user = User::factory()->create();
        $this->assertEquals([], $user->getJWTCustomClaims());
    }

    public function test_sends_email_verification_notification()
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->sendEmailVerificationNotification();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_sends_password_reset_notification()
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->sendPasswordResetNotification('token');
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_user_can_have_tasks()
    {
        $assigner = User::factory()->create();
        $user = User::factory()->create();
        $task = Task::factory()->create();

        // Attach the task to the user
        $user->tasks()->attach($task->id, ['assigned_by' => $assigner->id]);

        $this->assertTrue($user->tasks->contains($task));
    }
}
