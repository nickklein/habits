<?php

namespace NickKlein\Habits\Repositories;

use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
     */
    public function getDailyTotalsByHabitId(int $userId, string $timezone = 'UTC', array $habitIds, ?string $startRange = null, ?string $endRange = null)
    {
                // Validate the timezone before using it
        $safeTimezone = $this->getSafeTimezone($timezone);
        
        // Create a date expression variable that we'll reuse exactly the same way
        $dateExpression = 'DATE(CONVERT_TZ(start_time, "UTC", ?))';
        
        // Create a secure parameterized query with identical expressions
        $query = HabitTime::query()
            ->where('user_id', $userId)
            ->whereIn('habit_id', $habitIds)
            ->when($startRange, function ($query) use ($startRange, $endRange) {
                return $query->whereBetween('start_time', [$startRange, $endRange]);
            });
        
        // Use DB raw expressions that explicitly use the same alias in both SELECT and GROUP BY
        $collection = $query->selectRaw($dateExpression . ' as date_column, SUM(duration) as total_duration', [$safeTimezone])
            ->groupBy(DB::raw('date_column'))
            ->orderBy('date_column')
            ->get();

        return $collection;
    }


    /**
     * Get Weekly Totals by Habit ID
     *
     */
    public function getWeeklyTotalsByHabitId(int $userId, string $timezone = 'UTC', array $habitIds, ?string $startRange = null, ?string $endRange = null)
    {
        // Validate the timezone before using it
        $safeTimezone = $this->getSafeTimezone($timezone);
        
        // Create a subquery with the timezone conversion
        $subQuery = HabitTime::selectRaw(
            'id, user_id, habit_id, duration, ' . 
            'YEARWEEK(CONVERT_TZ(start_time, "UTC", ?), 3) as week_column', 
            [$safeTimezone]
        )
        ->where('user_id', $userId)
        ->whereIn('habit_id', $habitIds)
        ->when($startRange, function ($query) use ($startRange, $endRange) {
            return $query->whereBetween('start_time', [$startRange, $endRange]);
        });

        // Query the subquery to get the totals by week
        return DB::query()
            ->fromSub($subQuery, 'converted_weeks')
            ->select('week_column as week')
            ->addSelect(DB::raw('SUM(duration) as total_duration'))
            ->groupBy('week_column')
            ->orderBy('week_column')
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
     * Get active habit transaction elapsed seconds
     *
     * @param integer $habitId
     * @param integer $userId
     * @param string $timezone
     * @return int|null
     */
    public function getActiveHabitElapsedSeconds(int $habitId, int $userId, string $timezone = 'UTC'): ?int
    {
        $habitTime = HabitTime::where('habit_id', $habitId)
            ->where('user_id', $userId)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first();

        if (!$habitTime) {
            return null;
        }

        // start_time is stored in UTC, so parse it as UTC then get current time in user's timezone
        $startTime = Carbon::parse($habitTime->start_time, 'UTC');
        $now = Carbon::now($timezone);

        // Convert both to UTC for accurate comparison
        $elapsedSeconds = $now->diffInSeconds($startTime);

        return $elapsedSeconds;
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
            $habitTime->end_time = Carbon::now('UTC');
            $habitTime->duration = Carbon::parse($habitTime->start_time)->diffInSeconds($habitTime->end_time);
            $habitTime->save();
        }
    }
    /**
    * Validate timezone to ensure it's safe to use in queries
    *
    * @param string $timezone
    * @return string
    */
    private function getSafeTimezone(string $timezone): string
    {
        $validTimezones = timezone_identifiers_list();
        
        if (in_array($timezone, $validTimezones)) {
            return $timezone;
        }
        
        return 'UTC';
    }

    /**
     * Grab latest transactions
     *
     **/
    public function fetchNewHabitTransactions(string $lastUpdatedAt)
    {
        return HabitTime::where('updated_at', '>', $lastUpdatedAt)
            ->get();
    }
}
