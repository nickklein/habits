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
        $verbs = ['Read', 'Drink', 'Write', 'Exercise', 'Stretch', 'Cook', 'Walk', 'Listen to'];
        $nouns = ['Book', 'Water', 'Journal', 'Music', 'Podcast', 'News', 'Language Lesson'];

        $faker = Faker::create();
        Habit::unguard();
        for($row = 0; $row <= $count; $row++) {
            $habitName = $faker->randomElement($verbs) . ' ' . $faker->randomElement($nouns);
            Habit::create([
                'name' => $habitName,
            ]);
        }
        Habit::reguard();
    }

}
