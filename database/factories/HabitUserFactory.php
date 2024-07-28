<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class HabitUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::InRandomOrder()->first()->id,
            'habit_id' => $this->faker->numberBetween(1, 8),
            'streak_time_goal' => $this->faker->numberBetween(60 * 5, 3600 * 5),
            'streak_time_type' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
            'streak_type' => $this->faker->randomElement(['time', 'count']),
        ];
    }
}
