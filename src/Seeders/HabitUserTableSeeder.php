<?php

namespace NickKlein\Habits\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitUser;
use Faker\Factory as Faker;

class HabitUserTableSeeder
{
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
        $faker = Faker::create();
        $user = config('auth.providers.users.model');
        $user = $user::InRandomOrder()->first();

        foreach ($habits as $habit) {
            HabitUser::create(
                [
                    'habit_id' => $habit->habit_id,
                    'user_id' => $user->id ?? 1,
                    'streak_goal' => $faker->numberBetween(60 * 5, 3600),
                    'streak_time_type' => $faker->randomElement(['daily', 'weekly']),
                    'habit_type' => $faker->randomElement(['time']),
                ]
            );
        }
        HabitUser::reguard();
    }
}
