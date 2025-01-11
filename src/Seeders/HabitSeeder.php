<?php

namespace NickKlein\Habits\Seeders;

use NickKlein\Habits\Models\Habit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HabitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Habit::factory()->count(8)->create();
    }
}
