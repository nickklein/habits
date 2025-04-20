<?php

namespace NickKlein\Habits\Services;

use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitUser;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class HabitService
{
    // Create pagination constant
    const PAGINATE_LIMIT = 100;

    public function __construct(private HabitTypeFactory $habitTypeFactory)
    {
        //
    }

    public function getTransactions(int $userId, string $timezone = 'UTC'): LengthAwarePaginator
    {

        $habitTimes = HabitTime::select('habit_times.id', 'habits.name', 'start_time', 'end_time', 'duration', 'habit_type')
            ->join('habits', 'habit_times.habit_id', '=', 'habits.habit_id')
            ->join('habit_user', function ($join) {
                $join->on('habit_user.user_id', '=', 'habit_times.user_id')
                    ->on('habit_user.habit_id', '=', 'habit_times.habit_id');
            })
            ->where('habit_times.user_id', $userId)
            ->orderBy('id', 'desc')
            ->paginate(self::PAGINATE_LIMIT);

        $habitTimes->getCollection()->transform(function ($habitTime) use ($timezone) {
            $handler = $this->habitTypeFactory->getHandler($habitTime->habit_type);
            
            $formattedValue = $handler->formatValue($habitTime->duration);
            
            return [
                'id' => $habitTime->id,
                'name' => $habitTime->name,
                // Convert start time to the user's timezone
                'start_time' => Carbon::parse($habitTime->start_time)->setTimezone($timezone)->format('M j, Y, H:i:s'),
                // Convert end time to the user's timezone (if not null)
                'end_time' => $habitTime->end_time 
                    ? Carbon::parse($habitTime->end_time)->setTimezone($timezone)->format('M j, Y, H:i:s') 
                    : null,
                'duration' => $formattedValue['value'] . ' '. $formattedValue['unit'],
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

    /**
     * Manage Habit Time by turning it on/off
     *
     * @param integer $habitId
     * @param integer $userId
     * @param string $timezone
     * @return boolean
     *
     */
    public function manageHabitTransaction(int $habitId, int $userId, string $timezone = 'UTC', string $status): bool
    {
        $habitUser = HabitUser::where('habit_id', $habitId)->where('user_id', $userId)->first();
        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);

        return $handler->recordValue($habitId, $userId, 0, $timezone, $status);
    }

    public function updateHabitTransaction(int $habitTimeId, int $userId, string $timezone = 'UTC', int $habitId, int $value = 0, string $startDate, string $startTime, string $endDate, string $endTime): bool
    {
        // Need to figure out what the type for this habit is for the user
        $habitUser = HabitUser::where('habit_id', $habitId)->where('user_id', $userId)->first();
        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);

        return $handler->updateValue($habitTimeId, $userId, $timezone, $habitId, $value, $startDate, $startTime, $endDate, $endTime);
    }

    public function storeHabitTransaction(int $userId, string $timezone = 'UTC', int $habitId, int $value = 0, string $startDate, string $startTime, string $endDate, string $endTime): bool
    {
        // Need to figure out what the type for this habit is for the user
        $habitUser = HabitUser::where('habit_id', $habitId)->where('user_id', $userId)->first();
        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);

        return $handler->storeValue($userId, $timezone, $habitId, $value, $startDate, $startTime, $endDate, $endTime);
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
}
