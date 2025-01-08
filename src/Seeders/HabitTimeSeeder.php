<?php

namespace NickKlein\Habits\Seeders;

use Database\Factories\HabitTimeFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HabitTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $baseDate = now()->subDays(15); // starting from 30 days ago
        $numberOfRecords = 15;
        $habitIdMax = 8;

        for ($habitId = 1; $habitId < $habitIdMax; $habitId++) {
            for ($i = 0; $i <= $numberOfRecords; $i++) {
                $startDate = (clone $baseDate)->addDays($i);
                $endDate = (clone $startDate)->addHours(rand(1, 7)); // end time can be a random hours after start

                HabitTimeFactory::new()->create([
                    'user_id' => 1,
                    'habit_id' => $habitId,
                    'start_time' => $startDate->format('Y-m-d H:i:s'),
                    'end_time' => $endDate->format('Y-m-d H:i:s'),
                    'duration' => $endDate->diffInSeconds($startDate), // calculated based on difference
                ]);
            }
        }
    }
}
