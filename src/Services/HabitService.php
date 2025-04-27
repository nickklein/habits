<?php

namespace NickKlein\Habits\Services;

use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitUser;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use NickKlein\Habits\Events\HabitEndedEvent;

class HabitService
{
    // Create pagination constant
    const PAGINATE_LIMIT = 100;

    public function __construct()
    {
        //
    }

    public function getTransactions(int $userId, string $timezone = 'UTC'): LengthAwarePaginator
    {

        $habitTimes = HabitTime::select('habit_times.id', 'habits.name', 'start_time', 'end_time', 'duration')
            ->join('habits', 'habit_times.habit_id', '=', 'habits.habit_id')
            ->where('habit_times.user_id', $userId)
            ->orderBy('id', 'desc')
            ->paginate(self::PAGINATE_LIMIT);

        $habitTimes->getCollection()->transform(function ($habitTime) use ($timezone) {
            return [
                'id' => $habitTime->id,
                'name' => $habitTime->name,
                // Convert start time to the user's timezone
                'start_time' => Carbon::parse($habitTime->start_time)->setTimezone($timezone)->format('M j, Y, H:i:s'),
                // Convert end time to the user's timezone (if not null)
                'end_time' => $habitTime->end_time 
                    ? Carbon::parse($habitTime->end_time)->setTimezone($timezone)->format('M j, Y, H:i:s') 
                    : null,
                'duration' => $this->convertSecondsToMinutesOrHours($habitTime->duration)
            ];
        });


        return $habitTimes;
    }

    /**
     * Delete a habit time
     *
     * @param integer $habitTimeId
     * @param integer $userId
     * @return boolean
     */
    public function deleteHabitTime(int $habitTimeId, int $userId): bool
    {
        $habitTime = HabitTime::where('id', '=', $habitTimeId)
            ->where('user_id', '=', $userId);

        return $habitTime->delete();
    }

    public function updateHabitTime(int $habitTimeId, int $userId, string $timezone = 'UTC', int $habitId, string $startDate, string $startTime, string $endDate, string $endTime): bool
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
        event(new HabitEndedEvent($userId, $timezone, $habitTime));

        return $habitTime->save();
    }

    public function storeHabitTime(int $userId, string $timezone = 'UTC', int $habitId, string $startDate, string $startTime, string $endDate, string $endTime): bool
    {
        $startDateTime = $startDate . ' ' . $startTime;
        $endDateTime = $endDate . ' ' . $endTime;

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $startDateTime, $timezone)->setTimezone('UTC');
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $endDateTime, $timezone)->setTimezone('UTC');

        $habitTime = new HabitTime;
        $habitTime->habit_id = $habitId;
        $habitTime->user_id = $userId;
        $habitTime->start_time = $start;
        $habitTime->end_time = $end;
        $habitTime->duration = Carbon::parse($startDateTime)->diffInSeconds($endDateTime);
        event(new HabitEndedEvent($userId, $timezone, $habitTime));

        return $habitTime->save();
    }

    /**
     * Get specific Habit
     * @TODO: Move to Repository
     * @param integer $habitId
     * @return HabitUser
     */
    public function getHabit(int $habitId, int $userId)
    {
        return HabitUser::with(['habit', 'children', 'parent'])
            ->where('habit_id', $habitId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get habits
     *
     * @TODO: Move to Repository
     * @return void
     */
    public function getHabits()
    {
        return Habit::select(DB::raw('habit_id AS value'), DB::raw('name AS label'))
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all habit times for a user
     * @param integer $userId
     * @param integer $habitTimesId
     * @return array
     */
    public function getHabitTime(int $userId, string $timezone = 'UTC',  int $habitTimesId): array
    {
        $habitTime = HabitTime::where('id', $habitTimesId)
            ->select('id', 'habit_id', 'start_time', 'end_time')
            ->where('user_id', $userId)
            ->first();

        return [
            'id' => $habitTime->id,
            'habit_id' => $habitTime->habit_id,
            'start_date' => Carbon::parse($habitTime->start_time)->setTimezone($timezone)->format('Y-m-d'),
            'start_time' => Carbon::parse($habitTime->start_time)->setTimezone($timezone)->format('H:i:s'),
            'end_date' => $habitTime->end_time ? Carbon::parse($habitTime->end_time)->setTimezone($timezone)->format('Y-m-d') : null,
            'end_time' => $habitTime->end_time ? Carbon::parse($habitTime->end_time)->setTimezone($timezone)->format('H:i:s') : null,
        ];
    }

    /**
     * Convert seconds into minutes or hours
     *
     * @param integer $seconds
     * @return string
     * @deprecated version
     */
    public function convertSecondsToMinutesOrHours(int $seconds): string
    {
        $minutes = $seconds / 60;
        $hours = $minutes / 60;

        if ($hours >= 1) {
            return number_format($hours, 1) . 'h';
        }

        return number_format($minutes, 1) . 'm';
    }

    /**
     * Convert seconds into minutes or hours
     *
     * @param integer $seconds
     * @return array
     */
    public function convertSecondsToMinutesOrHoursV2(int $seconds): array
    {
        $minutes = $seconds / 60;
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
     * Calculate percentae difference between two numbers
     *
     * @param integer $oldValue
     * @param integer $newValue
     * @return integer
     */
    public function percentageDifference(int $oldValue, int $newValue): int
    {
        if ($oldValue == 0) {
            return 0; // To avoid division by zero
        }

        return floor((($newValue - $oldValue) / $oldValue) * 100);
    }

    public function fetchNewHabitTransactions(int $lastTransactionId, HabitService $habitService)
    {
        return HabitTime::where('id', $lastTransactionId)
            ->get();
    }
}
