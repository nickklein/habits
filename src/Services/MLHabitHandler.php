<?php

namespace NickKlein\Habits\Services;

use Carbon\Carbon;
use NickKlein\Habits\Interfaces\HabitTypeInterface;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Traits\EnvironmentAwareTrait;

class MLHabitHandler implements HabitTypeInterface
{
    use EnvironmentAwareTrait;
    
    /**
     * Constructor
     *
     */
    public function __construct()
    {
        //
    }
    
    /**
     * Format a value for display
     *
     * @param int $value Seconds
     * @return array
     */
    public function formatValue(int $value): array
    {
        $liters = $value / 1000;

        if ($liters >= 1) {
            return [
                'value' => number_format($liters, 1),
                'unit' => 'L',
                'unit_full' => 'liters',
            ];
        }

        return [
            'value' => number_format($value, 0),
            'unit' => 'ml',
            'unit_full' => 'milliliters',
        ];
    }

    /**
     * Format a goal value for display
     *
     * @param HabitUser $habitUser
     * @return array
     */
    public function formatGoal(HabitUser $habitUser): array
    {
        if (isset($habitUser->streak_goal)) {
            $convertedGoalTime = $this->formatValue($habitUser->streak_goal);
            return [
                'total' => $convertedGoalTime['value'],
                'unit' => $convertedGoalTime['unit'],
                'type' => $habitUser->streak_time_type,
            ];
        }

        return ['total' => null, 'unit' => null, 'type' => $habitUser->streak_time_type];
    }
    
    /**
     * Get the label for the unit
     *
     * @return string
     */
    public function getUnitLabel(): string
    {
        return 'ml';
    }
    
    /**
     * Get the label for the unit (full form)
     *
     * @return string
     */
    public function getUnitLabelFull(): string
    {
        return 'mililiters';
    }
    
    /**
     * Format the difference between two values for display in text
     *
     * @param int $value1 Seconds
     * @param int $value2 Seconds
     * @return string
     */
    public function formatDifference(int $value1, int $value2): string
    {
        // Convert to minutes for display
        $minuteDifference = abs($value1 - $value2) / 60;

        return round($minuteDifference);
    }
    
    /**
     * Format the value for streak goal display
     *
     * @param int $goalValue Seconds
     * @return string
     */
    public function formatStreakGoal(int $goalValue): string
    {
        if ($goalValue >= 1000) {
            return round($goalValue / 1000, 1) . 'L';
        }

        return $goalValue . 'ml';
    }
    
    /**
     * Calculate the percentage difference between two values
     *
     * @param int $value1
     * @param int $value2
     * @return array Two percentages, relative to the max of the two values
     */
    public function calculatePercentageDifference(int $value1, int $value2): array
    {
        $maxValue = max($value1, $value2);

        if ($maxValue == 0) {
            return [0, 0];
        }

        $percentage1 = ($value1 / $maxValue) * 100;
        $percentage2 = ($value2 / $maxValue) * 100;

        return [$percentage1, $percentage2];
    }
    
    /**
     * Check if a value meets the goal
     *
     * @param int $value Seconds
     * @param int $goalValue Seconds
     * @return bool
     */
    public function meetsGoal(int $value, int $goalValue): bool
    {
        return $value >= $goalValue;
    }
    
    /**
     * Record a time-based habit
     * Starts or stops a habit timer
     *
     * @param int $habitId
     * @param int $userId
     * @param string $timezone
     * @param array $fields ('status')
     * @return bool
     */

    public function recordValue(int $habitId, int $userId, string $timezone = 'UTC', array $fields): bool
    {
        $habitTime = new HabitTime;
        $this->setModelConnection($habitTime);
        $habitTime->habit_id = $habitId;
        $habitTime->user_id = $userId;
        $habitTime->start_time = Carbon::now($timezone)->timezone('UTC');
        $habitTime->end_time = Carbon::now($timezone)->timezone('UTC');
        $habitTime->duration = $fields['value'] ?? 1;

        return $habitTime->save();
    }

    public function updateValue(int $habitTimeId, int $userId, string $timezone = 'UTC', array $fields): bool
    {
        $habitTime = HabitTime::on($this->getDatabaseConnection())
            ->where('id', $habitTimeId)
            ->where('user_id', $userId)
            ->first();

        // If record isn't found, return false
        if (!$habitTime) {
            return false;
        }

        $startDateTime = Carbon::parse("{$fields['start_date']} {$fields['start_time']}", $timezone)->timezone('UTC');
        $endDateTime = Carbon::parse("{$fields['end_date']} {$fields['end_time']}", $timezone)->timezone('UTC');

        $habitTime->habit_id = $fields['habit_id'];
        $habitTime->start_time = $startDateTime;
        $habitTime->end_time = $endDateTime;
        $habitTime->duration = $fields['value'];

        return $habitTime->save();
    }

    /**
     * Stores a new row in the habit_time(habit_transaction) table
     *
     * @param $fields (int $value = 0, string $startDate, string $startTime, string $endDate, string $endTime)
     **/
    public function storeValue(int $userId, string $timezone = 'UTC', int $habitId, array $fields): bool
    {
        $start = $end = Carbon::now($timezone)->timezone('UTC');

        $habitTime = new HabitTime;
        $this->setModelConnection($habitTime);
        $habitTime->habit_id = $habitId;
        $habitTime->user_id = $userId;
        $habitTime->start_time = $start;
        $habitTime->end_time = $end;
        $habitTime->duration = $fields['value'];

        return $habitTime->save();
    }
}
