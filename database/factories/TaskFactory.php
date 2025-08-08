<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->text(200),
            'created_by' => User::all()->random()->id,
            'status' => $this->faker->randomElement(['pending', 'in progress', 'completed']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'project_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
