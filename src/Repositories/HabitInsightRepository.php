<?php

namespace NickKlein\Habits\Repositories;

use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use NickKlein\Habits\Events\HabitEndedEvent;

class HabitInsightRepository
{
    /**
     * Get Weekly Averages By Habit ID
     *
     * @param integer $userId
     * @param integer $habitId
     * @param integer $weekOffset
     * @return integer
     */
    public function getAveragesByHabitId(int $userId, array $habitIds, Carbon $startOfRange, Carbon $endOfRange, int $days = 7): int
    {

        $dailyTotals = HabitTime::select(DB::raw('DATE(start_time) as date'), DB::raw('SUM(duration) as total_duration'))
            ->where('user_id', $userId)
            ->whereIn('habit_id', $habitIds)
            ->whereBetween('start_time', [$startOfRange, $endOfRange])
            ->groupBy(DB::raw('DATE(start_time)'))
            ->orderBy('date')
            ->get();

        return $dailyTotals->avg('total_duration') ?? 0;
    }


    public function getSummationByHabitId(int $userId, array $habitIds, Carbon $startOfRange, Carbon $endOfRange): int
    {

        $dailyTotals = HabitTime::select(DB::raw('DATE(start_time) as date'), DB::raw('SUM(duration) as total_duration'))
            ->where('user_id', $userId)
            ->whereIn('habit_id', $habitIds)
            ->whereBetween('start_time', [$startOfRange, $endOfRange])
            ->groupBy(DB::raw('DATE(start_time)'))
            ->orderBy('date')
            ->get();

        $sum = $dailyTotals->sum('total_duration');

        return $sum;
    }

    /**
     * Get Daily Totlals by Habit ID
     *
     * @param integer $userId
     * @param integer $habitId
     * @return Collection
     */
    public function getDailyTotalsByHabitId(int $userId, array $habitIds, ?string $startRange = null, ?string $endRange = null, string $timezone = 'UTC')
    {
        // Get the raw data first without grouping
        $query = HabitTime::select('start_time', 'duration')
            ->where('user_id', $userId)
            ->whereIn('habit_id', $habitIds)
            ->when($startRange, function ($query) use ($startRange, $endRange) {
                return $query->whereBetween('start_time', [$startRange, $endRange]);
            })
            ->orderBy('start_time');
        
        $rawData = $query->get();
        
        // Group it by user's local date
        $groupedByUserDate = collect();
        
        foreach ($rawData as $record) {
            // Convert UTC time to user's timezone to get the correct date
            $localDate = Carbon::parse($record->start_time)
                ->setTimezone($timezone)
                ->format('Y-m-d');
            
            if (!isset($groupedByUserDate[$localDate])) {
                $groupedByUserDate[$localDate] = (object)[
                    'date' => $localDate,
                    'total_duration' => 0
                ];
            }
            
            $groupedByUserDate[$localDate]->total_duration += $record->duration;
        }
        
        return $groupedByUserDate->values()->sortBy('date')->values();
    }


    /**
     * Get Weekly Totals by Habit ID
     *
     * @param integer $userId
     * @param integer $habitId
     * @return Collection
     */
    public function getWeeklyTotalsByHabitId(int $userId, array $habitIds, ?string $startRange = null, ?string $endRange = null): Collection
    {
        return HabitTime::select(DB::raw('YEARWEEK(start_time, 1) as week'), DB::raw('SUM(duration) as total_duration'))
            ->where('user_id', $userId)
            ->whereIn('habit_id', $habitIds)
            ->when($startRange, function ($query) use ($startRange, $endRange) {
                return $query->whereBetween('start_time', [$startRange, $endRange]);
            })
            ->groupBy(DB::raw('YEARWEEK(start_time, 1)'))
            ->orderBy('week', 'asc')
            ->get();
    }

    /**
     * Get Weekly Totals by Habit ID
     *
     * @param integer $userId
     * @param integer $habitId
     * @return Collection
     */
    public function getWeeklyDurationByDateRange(int $userId, int $habitId): Collection
    {
        return HabitTime::select(DB::raw('YEARWEEK(start_time, 1) as week'), DB::raw('SUM(duration) as total_duration'))
            ->where('user_id', $userId)
            ->where('habit_id', $habitId)
            ->groupBy(DB::raw('YEARWEEK(start_time, 1)'))
            ->orderBy('week', 'asc')
            ->get();
    }

    /**
     * Check if Habit is active
     *
     * @param integer $habitId
     * @param integer $userId
     * @return boolean
     */
    public function isHabitActive(int $habitId, int $userId): bool
    {
        return HabitTime::where('habit_id', $habitId)
            ->where('user_id', $userId)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->exists();
    }

    /**
     * Checks if habit is streakable
     *
     * @param integer $habitId
     * @param integer $userId
     * @return boolean
     */
    public function isHabitStreakable(int $habitId, int $userId): bool
    {
        return HabitUser::where('habit_id', $habitId)
            ->where('user_id', $userId)
            ->where('streak_goal', '>', 0)
            ->exists();
    }

    /**
     * Check if any habit is active
     *
     * @param integer $userId
     * @return boolean
     */
    public function anyHabitActive(int $userId): bool
    {
        return HabitTime::where('user_id', $userId)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->exists();
    }

    /**
     * End all active habits
     *
     * @param integer $userId
     * @param string $timezone
     * @param array $habitIds
     * @return void
     */
    public function endAllActiveHabits(int $userId, string $timezone = 'UTC', array $habitIds = []): void
    {
        $activeHabits = HabitTime::with('habit')->when($habitIds, function ($query) use ($habitIds) {
            return $query->whereIn('habit_id', $habitIds);
        })
            ->where('user_id', $userId)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->get();

        foreach ($activeHabits as $habitTime) {
            $habitTime->end_time = date('Y-m-d H:i:s');
            $habitTime->end_time = Carbon::now('UTC');
            $habitTime->duration = Carbon::parse($habitTime->start_time)->diffInSeconds($habitTime->end_time);
            event(new HabitEndedEvent($userId, $habitTime));
            $habitTime->save();
        }
    }
}
