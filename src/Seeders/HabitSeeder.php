<?php

namespace NickKlein\Habits\Seeders;

use NickKlein\Habits\Models\Habit;
use Faker\Factory as Faker;

class HabitSeeder
{
    /**
     *  Seed Habit data
     *
     * @return void
     */
    public function run(int $count = 1)
    {
        $faker = Faker::create();
        Habit::unguard();
        for($row = 0; $row <= $count; $row++) {
            Habit::create([
                'name' => $faker->word(),
            ]);
        }
        Habit::reguard();
    }

}
