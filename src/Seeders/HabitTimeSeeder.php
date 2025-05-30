<?php

namespace NickKlein\Habits\Seeders;

use Illuminate\Database\Eloquent\Collection;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitUser;

class HabitTimeSeeder
{
    const TIME_TYPE = 'time';
    const ML_TYPE = 'ml';
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(array $habitIds = [], int $days = 15)
    {
        if (empty($habitIds)) {
            $habitsUser = HabitUser::all();
            $this->generateHabitTransactions($habitsUser, $days);
        }

        $habitsUser = HabitUser::whereIn('habit_id', $habitIds)->get();
        $this->generateHabitTransactions($habitsUser, $days);
        return;
    }
    
    private function generateHabitTransactions(Collection $habitsUser, int $days = 15)
    {
        $baseDate = now()->subDays($days);
        foreach($habitsUser as $habit) {
            for ($i = 0; $i <= $days; $i++) {
                if ($habit->habit_type === self::TIME_TYPE) {
                    $this->generateHabitTimes($habit, $baseDate, $i);
                    continue;
                }

                if ($habit->habit_type === self::ML_TYPE) {
                    $this->generateHabitML($habit, $baseDate, $i);
                }
                
                $this->generateHabitUnits($habit, $baseDate, $i);
            }
        }
    }

    private function generateHabitTimes($habit, $baseDate, $day)
    {
        $startDate = (clone $baseDate)->addDays($day);
        $endDate = (clone $startDate)->addHours(rand(1, 7)); // end time can be a random hours after start

        HabitTime::create([
            'user_id' => 1,
            'habit_id' => $habit->habit_id,
            'start_time' => $startDate->format('Y-m-d H:i:s'),
            'end_time' => $endDate->format('Y-m-d H:i:s'),
            'duration' => $startDate->diffInSeconds($endDate), // calculated based on difference
        ]);
    }

    private function generateHabitUnits($habit, $baseDate, $day)
    {
        $startDate = $endDate = (clone $baseDate)->addDays($day);

        for($today = 0; $today < rand(1,4); $today++) {
            HabitTime::create([
                'user_id' => 1,
                'habit_id' => $habit->habit_id,
                'start_time' => $startDate->format('Y-m-d H:i:s'),
                'end_time' => $endDate->format('Y-m-d H:i:s'),
                'duration' => 1,
            ]);
        }
    }

    private function generateHabitML($habit, $baseDate, $day)
    {
        $startDate = $endDate = (clone $baseDate)->addDays($day);

        for($today = 0; $today < rand(1,4); $today++) {
            HabitTime::create([
                'user_id' => 1,
                'habit_id' => $habit->habit_id,
                'start_time' => $startDate->format('Y-m-d H:i:s'),
                'end_time' => $endDate->format('Y-m-d H:i:s'),
                'duration' => rand(200, 300),
            ]);
        }
    }
}
