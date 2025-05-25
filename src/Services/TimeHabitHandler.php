<?php

namespace NickKlein\Habits\Services;

use Carbon\Carbon;
use NickKlein\Habits\Interfaces\HabitTypeInterface;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitUser;

class TimeHabitHandler implements HabitTypeInterface
{
    
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
        $minutes = $value / 60;
        $hours = $minutes / 60;

        if ($hours >= 1) {
            return [
                'value' => number_format($hours, 1),
                'unit' => 'hrs',
                'unit_full' => 'hours',
            ];
        }

        return [
            'value' => number_format($minutes, 1),
            'unit' => 'min',
            'unit_full' => 'minutes',
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
        return 'm';
    }
    
    /**
     * Get the label for the unit (full form)
     *
     * @return string
     */
    public function getUnitLabelFull(): string
    {
        return 'minutes';
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
        return round($goalValue / 60) . 'm';
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
     * @param int $value Not used for time habits - use string status instead
     * @param string $timezone
     * @param string $status 'on' or 'off'
     * @return bool
     */
    public function recordValue(int $habitId, int $userId, int $value = 0, string $timezone = 'UTC', string $status = 'on'): bool
    {
        if ($status === 'on') {
            $habitTime = new HabitTime;
            $habitTime->habit_id = $habitId;
            $habitTime->user_id = $userId;
            // Convert current user-local time to UTC
            $habitTime->start_time = Carbon::now($timezone)->timezone('UTC');
            $habitTime->end_time = null;

            return $habitTime->save();
        }

        $habitTime = HabitTime::where('habit_id', $habitId)
            ->where('user_id', $userId)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first();

        if (!$habitTime) {
            return false;
        }

        $habitTime->end_time = Carbon::now($timezone)->timezone('UTC');
        $habitTime->duration = Carbon::parse($habitTime->start_time)->diffInSeconds($habitTime->end_time);

        return $habitTime->save();
    }

    public function updateValue(int $habitTimeId, int $userId, string $timezone = 'UTC', int $habitId, int $value = 0, string $startDate, string $startTime, string $endDate, string $endTime): bool
    {
        $habitTime = HabitTime::where('id', $habitTimeId)
            ->where('user_id', $userId)
            ->first();

        // If record isn't found, return false
        if (!$habitTime) {
            return false;
        }

        $startDateTime = Carbon::parse("{$startDate} {$startTime}", $timezone)->timezone('UTC');
        $endDateTime = Carbon::parse("{$endDate} {$endTime}", $timezone)->timezone('UTC');

        $habitTime->habit_id = $habitId;
        $habitTime->start_time = $startDateTime;
        $habitTime->end_time = $endDateTime;
        $habitTime->duration = Carbon::parse($startDateTime)->diffInSeconds($endDateTime);

        return $habitTime->save();
    }

    /**
     * Stores a new row in the habit_time(habit_transaction) table
     *
     * @param $fields (int $value = 0, string $startDate, string $startTime, string $endDate, string $endTime)
     **/
    public function storeValue(int $userId, string $timezone = 'UTC', int $habitId, array $fields): bool
    {
        $startDateTime = $fields['start_date'] . ' ' . $fields['start_time'];
        $endDateTime = $fields['end_date'] . ' ' . $fields['end_time'];

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $startDateTime, $timezone)->setTimezone('UTC');
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $endDateTime, $timezone)->setTimezone('UTC');

        $habitTime = new HabitTime;
        $habitTime->habit_id = $habitId;
        $habitTime->user_id = $userId;
        $habitTime->start_time = $start;
        $habitTime->end_time = $end;
        $habitTime->duration = Carbon::parse($startDateTime)->diffInSeconds($endDateTime);

        return $habitTime->save();
    }
}
