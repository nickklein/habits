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
            $habitType = $this->faker->randomElement(['time', 'unit']);

            HabitUser::create(
                [
                    'habit_id' => $habit->habit_id,
                    'user_id' => $user->id ?? 1,
                    'streak_goal' => $this->getStreakGoal($habitType),
                    'streak_time_type' => $this->faker->randomElement(['daily', 'weekly']),
                    'habit_type' => $habitType,
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

        return $this->faker->numberBetween(1, 5);
    }
}
