<?php

namespace NickKlein\Habits\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitUser;
use Faker\Factory as Faker;

class HabitUserTableSeeder
{
    private $faker;
    /**
     * Run the database seeds.
     */
    public function run(array $habitIds = []): void
    {
        if (empty($habitIds)) {
            $habits = Habit::all();
            $this->generateHabitUser($habits);

            return;
        }

        $habits = Habit::whereIn('habit_id', $habitIds)->get();
        $this->generateHabitUser($habits);

        return;
    }

    private function generateHabitUser(Collection $habits)
    {
        HabitUser::unguard();
        $this->faker = Faker::create();
        $user = config('auth.providers.users.model');
        $user = $user::InRandomOrder()->first();

        foreach ($habits as $habit) {
            $habitType = $this->faker->randomElement(['time', 'unit', 'ml']);

            HabitUser::create(
                [
                    'habit_id' => $habit->habit_id,
                    'user_id' => $user->id ?? 1,
                    'streak_goal' => $this->getStreakGoal($habitType),
                    'streak_time_type' => $this->faker->randomElement(['daily', 'weekly']),
                    'habit_type' => $habitType,
                    'color_index' => $this->getRandomHexColor(),
                ]
            );
        }
        HabitUser::reguard();
    }

    private function getStreakGoal(string $habitType): int
    {
        if ($habitType === 'time') {
            return $this->faker->numberBetween(60 * 5, 3600);
        }

        if ($habitType === 'ml') {
            return $this->faker->numberBetween(1000, 2000);
        }

        return $this->faker->numberBetween(1, 5);
    }

    private function getRandomHexColor(): string
    {
        $colors = [
            '#3B82F6', // blue
            '#10B981', // emerald
            '#F59E0B', // amber
            '#8B5CF6', // violet
            '#EF4444', // red
            '#06B6D4', // cyan
            '#84CC16', // lime
            '#F97316', // orange
            '#EC4899', // pink
            '#6366F1', // indigo
            '#14B8A6', // teal
            '#A855F7', // purple
            '#22D3EE', // sky
            '#FB7185', // rose
            '#65A30D', // green
        ];

        return $this->faker->randomElement($colors);
    }
}
