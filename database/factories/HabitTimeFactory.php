<?php

namespace Database\Factories;

use App\Models\HabitTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HabitTime>
 */
class HabitTimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = HabitTime::class;

    public function definition()
    {
        return [
            'user_id' => 1,
            'habit_id' => $this->faker->numberBetween(1, 8),
            'start_time' => $this->faker->dateTimeBetween('-3 week', 'now')->format('Y-m-d H:i:s'),
            'end_time' => $this->faker->dateTimeBetween('-3 week', 'now')->format('Y-m-d H:i:s'),
            'duration' => $this->faker->numberBetween(1, 10000),
        ];
    }
}
