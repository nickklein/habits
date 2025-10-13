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
        Habit::unguard();
        $habits = [
            'Learning a language',
            'Exercising',   
            'Drinking water',
            'Standing',
        ];
        foreach($habits as $habit) {
            Habit::create([
                'name' => $habit,
            ]);
        }
        Habit::reguard();
    }

}
