<?php

namespace NickKlein\Habits\Seeders;

use NickKlein\Habits\Models\Habit;
use Faker\Factory as Faker;

class HabitSeeder
{
    /**
     *  Seed Habit data
     *
     * @param int $count Number of habits to generate
     * @return void
     */
    public function run(int $count = 1)
    {
        $faker = Faker::create();

        Habit::unguard();

        $habitTemplates = [
            'Learning {skill}',
            'Practicing {skill}',
            'Exercising',
            'Drinking water',
            'Standing',
            'Reading {activity}',
            'Meditating',
            'Journaling',
            'Stretching',
            'Walking',
            'Cooking {activity}',
            'Writing',
            'Playing {skill}',
            'Studying {skill}',
        ];

        for ($i = 0; $i < $count; $i++) {
            $template = $faker->randomElement($habitTemplates);

            $name = str_replace(
                ['{skill}', '{activity}'],
                [$faker->word(), $faker->word()],
                $template
            );

            Habit::create([
                'name' => ucfirst($name),
            ]);
        }

        Habit::reguard();
    }

}
