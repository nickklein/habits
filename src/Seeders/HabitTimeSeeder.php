<?php

namespace NickKlein\Habits\Seeders;

use Database\Factories\HabitTimeFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitTime;

class HabitTimeSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(array $habitIds = [], int $days = 15)
    {
        if (empty($habitIds)) {
            $habits = Habit::all();
            $this->generateHabitTimes($habits, $days);
        }

        $habits = Habit::whereIn('habit_id', $habitIds)->get();
        $this->generateHabitTimes($habits, $days);
        return;
    }
    
    private function generateHabitTimes(Collection $habits, int $days = 15)
    {
        $baseDate = now()->subDays($days);
        foreach($habits as $habit) {
            for ($i = 0; $i <= $days; $i++) {
                $startDate = (clone $baseDate)->addDays($i);
                $endDate = (clone $startDate)->addHours(rand(1, 7)); // end time can be a random hours after start

                HabitTime::create([
                    'user_id' => 1,
                    'habit_id' => $habit->habit_id,
                    'start_time' => $startDate->format('Y-m-d H:i:s'),
                    'end_time' => $endDate->format('Y-m-d H:i:s'),
                    'duration' => $endDate->diffInSeconds($startDate), // calculated based on difference
                ]);
            }
        }
    }
}
