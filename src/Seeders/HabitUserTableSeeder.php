<?php

namespace NickKlein\Habits\Seeders;

use NickKlein\Habits\Habit;
use NickKlein\Habits\HabitUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HabitUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $habits = Habit::all();
        foreach ($habits as $habit) {
            HabitUser::factory()->create(['habit_id' => $habit->habit_id]);
        }
    }
}
