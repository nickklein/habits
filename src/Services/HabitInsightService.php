<?php

namespace NickKlein\Habits\Services;

use App\Services\PushoverService;
use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use NickKlein\Habits\Events\HabitEndedEvent;

class HabitInsightService
{
    const HABIT_COLOR_INDEX = [
        1 => 'blue',
        2 => 'gray',
        3 => 'orange',
        4 => 'purple',
        5 => 'green',
        6 => 'green',
        /* 5 => 'red', */
        /* 6 => 'indigo', */
        7 => 'pink',
        8 => 'gray',
        9 => 'yellow',
        10 => 'teal',
        11 => 'lime',
        12 => 'amber',
        13 => 'cyan',
        14 => 'sky',
        15 => 'emerald',
        16 => 'violet',
        17 => 'rose'
    ];

    public function __construct()
    {
        //
    }



    /**
     * Get daily summary for habits
     *
     * @param integer $userId
     * @param Collection $habitsUser
     * @param HabitService $service
     * @param HabitInsightRepository $insightRepository
     * @return array
     */
    public function getDailySummaryForHabits(int $userId, string $timezone = 'UTC', Collection $habitsUser, HabitService $service, HabitInsightRepository $insightRepository): array
    {
        $dateRanges = [
            'daily' => [
                'start' => Carbon::today($timezone)->startOfDay()->setTimezone('UTC'),
                'end' => Carbon::today($timezone)->endOfDay()->setTimezone('UTC'),
            ],
            'weekly' => [
                'start' => Carbon::now($timezone)->startOfWeek()->setTimezone('UTC'),
                'end' => Carbon::now($timezone)->endOfWeek()->setTimezone('UTC'),
            ]
        ];
        $group = 0;
        $summaries = [];
        foreach ($habitsUser as $key => $habit) {
            $color = self::HABIT_COLOR_INDEX[$habit->habit_id];
            // Some habits have children, so we need to loop through them as well
            $summaries[$group] = $this->generateDailySummariesForUser($habit, $userId, $timezone, $dateRanges, $color, $service, $insightRepository);
            if ($habit->children) {
                foreach ($habit->children as $key => $child) {
                    $summaries[$group]['children'][] = $this->generateDailySummariesForUser($child, $userId, $timezone, $dateRanges, $color, $service, $insightRepository);
                }
            }
            $group++;
        }

        return $summaries;
    }


    public function generateDailySummariesForUser(HabitUser $habitUser, int $userId, string $timezone, array $dateRanges, string $color, HabitService $service, HabitInsightRepository $insightRepository)
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);
        $time = $this->fetchTotalDurationBasedOnStreakType($habitUser, $userId, $timezone, $habitIds, $dateRanges, $insightRepository);

        $currentTime = $this->convertTimeToSummaryPageFormat($service, $time);
        $goalTime = $this->convertGoalTimeToSummaryPageFormat($service, $habitUser);

        return [
            'id' => $habitUser->habit_id,
            'name' => $habitUser->habit->name,
            'current' => $currentTime,
            'goal' => $goalTime,
            'color' => $color,
        ];
    }

    /**
     * Generate daily notifications
     * This is for phone notifications
     * @todo dependency injection for HabitInsightRepository etc
     *
     * @param integer $userId
     * @param string $timezone
     * @return string
     */
    public function generateDailyNotification(int $userId, string $timezone = 'UTC'): string
    {
        //
        $notification = '';
        $streakSummary = $this->generateStreakSummary($userId, $timezone);
        foreach($streakSummary as $item) {
            $notification .= $item['name'] . '('.$item['type'].'): ' . $item['total'] . ', ';
        }

        return substr($notification, 0, -2);
    }

    /**
     * Generate streak summary
     * @todo dependency injection for HabitInsightRepository etc
     *
     * @param integer $userId
     * @param string $timezone
     * @return array
     */
    public function generateStreakSummary(int $userId, string $timezone = 'UTC'): array
    {
        // WARNING. Really messy code that needs to be cleaned up. 
        $streakSummary = [];
        $habitUser = HabitUser::with(['habit', 'children'])->where('user_id', $userId)
            ->whereIn('habit_id', [10, 11, 5, 9, 14, 15, 16, 19, 8, 4])
            ->whereNotNull('streak_time_goal')
            ->orderBy('streak_time_type', 'ASC')
            ->get();
        $notification = '';
        $insightsRepository = app(HabitInsightRepository::class);
        $habitService = app(HabitService::class);
        $startOfDay = Carbon::today($timezone)->startOfDay()->setTimezone('UTC');
        $endOfDay = Carbon::today($timezone)->endOfDay()->setTimezone('UTC');
        $startOfWeek = Carbon::today($timezone)->startOfWeek()->setTimezone('UTC');
        $endOfWeek = Carbon::today($timezone)->endOfWeek()->setTimezone('UTC');

        foreach($habitUser as $item) {
            $habitIdsArray = $this->fetchHabitIdsBasedOnHierarchy($item);

            if ($item->streak_time_type === 'daily') {
                $dailyTotals = $insightsRepository->getDailyTotalsByHabitId($userId, $timezone, $habitIdsArray, $startOfDay, $endOfDay);
                // if the total duration is higher than the goal, then don't show in the notification
                if ($dailyTotals->first() && $item->streak_time_goal < $dailyTotals->first()->total_duration) {
                    continue;
                }

                $name = $item->habit->name;
                $total = $habitService->convertSecondsToMinutesOrHours($dailyTotals->sum('total_duration'));
                $streakSummary[] = [
                    'name' => $name,
                    'type' => 'd',
                    'goal' => round($item->streak_time_goal / 60) . 'm',
                    'total' => $total,
                ];
            }

            if ($item->streak_time_type === 'weekly') {
                $weeklyTotals = $insightsRepository->getWeeklyTotalsByHabitId($userId, $timezone, $habitIdsArray, $startOfWeek, $endOfWeek);
                // if the total duration is higher than the goal, then don't show in the notification
                if ($weeklyTotals->first() && $item->streak_time_goal < $weeklyTotals->first()->total_duration) {
                    continue;
                }

                $name = $item->habit->name;
                $total = $habitService->convertSecondsToMinutesOrHours($weeklyTotals->sum('total_duration'));
                $streakSummary[] = [
                    'name' => $name,
                    'type' => 'w',
                    'goal' => round($item->streak_time_goal / 60) . 'm',
                    'total' => $total,
                ];
                $notification .= $name . '(w): ' . $total . ', ';
            }
        }

        return $streakSummary;
    }

    /**
     * Generate weekly notifications
     * This is for phone notifications
     *
     * @param integer $userId
     * @param string $timezone
     * @return string
     */
    public function generateWeeklyNotifications(int $userId, string $timezone = 'UTC')
    {

        $habits = Habit::whereIn('habit_id', [10, 12, 18, 5, 9])->get();
        $insightsRepository = app(HabitInsightRepository::class);
        $habitService = app(HabitService::class);

        $startOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->subDays(6)->setTimezone('UTC');
        $endOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->setTimezone('UTC');

        $startOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->subDays(6)->setTimezone('UTC');
        $endOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->setTimezone('UTC');

        $notification = '';
        foreach ($habits as $habit) {
            $thisWeek = $insightsRepository->getAveragesByHabitId($userId, [$habit->habit_id], $startOfRangeThisWeek, $endOfRangeThisWeek, 7);
            $weekBefore = $insightsRepository->getAveragesByHabitId($userId, [$habit->habit_id], $startOfRangeLastWeek, $endOfRangeLastWeek, 7);

            $name = $habit->name;
            $thisWeekAvg = $habitService->convertSecondsToMinutesOrHours($thisWeek);
            $percentageDifference = $habitService->percentageDifference($weekBefore, $thisWeek);

            $notification .= $name . ': ' . $thisWeekAvg . ' (' . $percentageDifference . '%), ';
        }

        return $notification;
    }

    /**
     * Manage Habit Time by turning it on/off
     *
     * @param integer $habitId
     * @param integer $userId
     * @param string $timezone
     * @return boolean
     *
     * @todo dependency injection for HabitInsightRepository
     */
    public function manageHabitTime(int $habitId, int $userId, string $timezone = 'UTC', string $status): bool
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

        event(new HabitEndedEvent($userId, $timezone, $habitTime));

        return $habitTime->save();
    }

    /**
     * Get Daily Highlights
     *
     */
    public function getDailySummaryHighlights(HabitUser $habitUser, string $timezone = 'UTC', HabitService $habitService, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $yesterday = Carbon::yesterday($timezone)->hour(0)->setTimezone('UTC');
        $yesterdayEnd = Carbon::yesterday($timezone)->hour(24)->setTimezone('UTC');

        $dayBeforeYesterday = Carbon::today($timezone)->subDays(2)->hour(0)->setTimezone('UTC');
        $dayBeforeYesterdayEnd = Carbon::today($timezone)->subDays(2)->hour(24)->setTimezone('UTC');

        // Grab the values for yesterday and the day before yesterday
        $yesterdayCollection = $habitInsightRepository->getDailyTotalsByHabitId($habitUser->user_id, $timezone, $habitIds, $yesterday, $yesterdayEnd);
        $dayOfBeforeYesterdayCollection = $habitInsightRepository->getDailyTotalsByHabitId($habitUser->user_id, $timezone, $habitIds, $dayBeforeYesterday, $dayBeforeYesterdayEnd);

        // Convert values to hours/minutes
        $yesterdayValues = $habitService->convertSecondsToMinutesOrHoursV2($yesterdayCollection->sum('total_duration'));
        $dayOfBeforeYesterdayValues = $habitService->convertSecondsToMinutesOrHoursV2($dayOfBeforeYesterdayCollection->sum('total_duration'));

        // Calculates the percentage difference between two numbers, where 1 is 100%, and the other one is the percentage difference
        $percentages = $this->calculatePercentageDifferenceBetweenTwoNumbers($yesterdayCollection->sum('total_duration'), $dayOfBeforeYesterdayCollection->sum('total_duration'));

        // The difference between the two numbers in minutes
        $minuteDifference = abs($yesterdayCollection->sum('total_duration') - $dayOfBeforeYesterdayCollection->sum('total_duration')) / 60;

        return [
            'description' => 'You did ' . round($minuteDifference) . ' ' . ($yesterdayCollection->sum('total_duration') > $dayOfBeforeYesterdayCollection->sum('total_duration') ? 'more' : 'less') . ' minutes yesterday than you did the day before',
            'barOne' => [
                "number" => $yesterdayValues['value'],
                "unit" => $yesterdayValues['unit_full'],
                "bar_text" => $yesterday->dayName,
                "width" => $percentages[0]
            ],
            'barTwo' => [
                "number" => $dayOfBeforeYesterdayValues['value'],
                "unit" => $dayOfBeforeYesterdayValues['unit_full'],
                "bar_text" => $dayBeforeYesterday->dayName,
                "width" => $percentages[1]
            ]
        ];
    }

    /**
     * Get Weekly Highlights
     *
     */
    public function getWeeklyAverageHighlights(HabitUser $habitUser, string $timezone = 'UTC', HabitService $habitService, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $startOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->subDays(6)->setTimezone('UTC');
        $endOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->setTimezone('UTC');

        $startOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->subDays(6)->setTimezone('UTC');
        $endOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->setTimezone('UTC');

        $thisWeek = $habitInsightRepository->getAveragesByHabitId($habitUser->user_id, $habitIds, $startOfRangeThisWeek, $endOfRangeThisWeek, 7);
        $weekBefore = $habitInsightRepository->getAveragesByHabitId($habitUser->user_id, $habitIds, $startOfRangeLastWeek, $endOfRangeLastWeek, 7);

        // Convert values to hours/minutes
        $thisWeekValues = $habitService->convertSecondsToMinutesOrHoursV2($thisWeek);
        $weekBeforeValues = $habitService->convertSecondsToMinutesOrHoursV2($weekBefore);

        $percentages = $this->calculatePercentageDifferenceBetweenTwoNumbers($thisWeek, $weekBefore);

        // The difference between the two numbers in minutes
        $minuteDifference = round(abs($weekBefore - $thisWeek) / 60);

        return [
            'description' => 'Last week, you averaged ' . $minuteDifference . ' ' . ($weekBefore < $thisWeek ? "more" : "fewer") . ' minutes than the week before',
            'barOne' => [
                "number" => $thisWeekValues['value'],
                "unit" => $thisWeekValues['unit_full'] . ' / day',
                "bar_text" => $startOfRangeThisWeek->format('j. F') . ' - ' . $endOfRangeThisWeek->format('j. F'),
                "width" => $percentages[0]
            ],
            'barTwo' => [
                "number" => $weekBeforeValues['value'],
                "unit" => $weekBeforeValues['unit_full'] . ' / day',
                "bar_text" => $startOfRangeLastWeek->format('j. F') . ' - ' . $endOfRangeLastWeek->format('j. F'),
                "width" => $percentages[1]
            ]
        ];
    }

    /**
     * Get weekly highlights for insights page
     * @param HabitUser $habitUser
     * @param HabitService $habitService
     * @param HabitInsightRepository $habitInsightRepository
     * @return array
     */
    public function weeklySummaryHighlights(HabitUser $habitUser, string $timezone = 'UTC', HabitService $habitService, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $startOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->subDays(6)->setTimezone('UTC');
        $endOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->setTimezone('UTC');

        $startOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->subDays(6)->setTimezone('UTC');
        $endOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->setTimezone('UTC');

        $thisWeek = $habitInsightRepository->getSummationByHabitId($habitUser->user_id, $habitIds, $startOfRangeThisWeek, $endOfRangeThisWeek);
        $weekBefore = $habitInsightRepository->getSummationByHabitId($habitUser->user_id, $habitIds, $startOfRangeLastWeek, $endOfRangeLastWeek);

        // Convert values to hours/minutes
        $thisWeekValues = $habitService->convertSecondsToMinutesOrHoursV2($thisWeek);
        $weekBeforeValues = $habitService->convertSecondsToMinutesOrHoursV2($weekBefore);

        $percentages = $this->calculatePercentageDifferenceBetweenTwoNumbers($thisWeek, $weekBefore);

        // The difference between the two numbers in minutes
        $minuteDifference = round(abs($weekBefore - $thisWeek) / 60);


        return [
            'description' => 'Last week, you did ' . $minuteDifference . ' ' . ($weekBefore < $thisWeek ? "more" : "fewer") . ' minutes in total than the week before',
            'barOne' => [
                "number" => $thisWeekValues['value'],
                "unit" => $thisWeekValues['unit_full'],
                "bar_text" => $startOfRangeThisWeek->format('j. F') . ' - ' . $endOfRangeThisWeek->format('j. F'),
                "width" => $percentages[0]
            ],
            'barTwo' => [
                "number" => $weekBeforeValues['value'],
                "unit" => $weekBeforeValues['unit_full'],
                "bar_text" => $startOfRangeLastWeek->format('j. F') . ' - ' . $endOfRangeLastWeek->format('j. F'),
                "width" => $percentages[1]
            ]
        ];
    }

    /**
     * Get monthly average highlights
     *
     */
    public function getMonthlyAverageHighlights(HabitUser $habitUser, string $timezone = 'UTC', HabitService $habitService, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $startOfRangeThisMonth = Carbon::now($timezone)->subMonths(1)->setTimezone('UTC');
        $endOfRangeThisMonth = Carbon::now($timezone)->setTimezone('UTC');

        $startOfRangeLastMonth = Carbon::now($timezone)->subMonths(2)->setTimezone('UTC');
        $endOfRangeLastMonth = Carbon::now($timezone)->subMonths(1)->setTimezone('UTC');

        $thisMonth = $habitInsightRepository->getAveragesByHabitId($habitUser->user_id, $habitIds, $startOfRangeThisMonth, $endOfRangeThisMonth, $startOfRangeThisMonth->diffInDays($endOfRangeThisMonth));
        $lastMonth = $habitInsightRepository->getAveragesByHabitId($habitUser->user_id, $habitIds, $startOfRangeLastMonth, $endOfRangeLastMonth, $startOfRangeLastMonth->diffInDays($endOfRangeLastMonth));

        // Convert values to hours/minutes
        $thisMonthValues = $habitService->convertSecondsToMinutesOrHoursV2($thisMonth);
        $lastMonthValues = $habitService->convertSecondsToMinutesOrHoursV2($lastMonth);

        // calculate percentages
        $percentages = $this->calculatePercentageDifferenceBetweenTwoNumbers($thisMonth, $lastMonth);

        $minuteDifference = round(abs($lastMonth - $thisMonth) / 60);

        return [
            'description' => 'Last month, you averaged ' . $minuteDifference . ' ' . ($lastMonth < $thisMonth ? "more" : "fewer") . ' minutes than the month before.',
            'barOne' => [
                "number" => $thisMonthValues['value'],
                "unit" => $thisMonthValues['unit_full'] . ' / day',
                "bar_text" => $startOfRangeThisMonth->format('j. F') . ' - ' . $endOfRangeThisMonth->format('j. F'),
                "width" => $percentages[0],
            ],
            'barTwo' => [
                "number" => $lastMonthValues['value'],
                "unit" => $lastMonthValues['unit_full'] . ' / day',
                "bar_text" => $startOfRangeLastMonth->format('j. F') . ' - ' . $endOfRangeLastMonth->format('j. F'),
                "width" => $percentages[1],
            ],
        ];
    }

    /**
     * Get Monthly Summary Highlights
     *
     */
    public function getMonthlySummaryHighlights(HabitUser $habitUser, string $timezone = 'UTC', HabitService $habitService, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $startOfRangeThisMonth = Carbon::now($timezone)->subMonths(1)->setTimezone('UTC');
        $endOfRangeThisMonth = Carbon::now($timezone)->setTimezone('UTC');

        $startOfRangeLastMonth = Carbon::now($timezone)->subMonths(2)->setTimezone('UTC');
        $endOfRangeLastMonth = Carbon::now($timezone)->subMonths(1)->setTimezone('UTC');

        $thisWeek = $habitInsightRepository->getSummationByHabitId($habitUser->user_id, $habitIds, $startOfRangeThisMonth, $endOfRangeThisMonth);
        $weekBefore = $habitInsightRepository->getSummationByHabitId($habitUser->user_id, $habitIds, $startOfRangeLastMonth, $endOfRangeLastMonth);

        // Convert values to hours/minutes
        $thisWeekValues = $habitService->convertSecondsToMinutesOrHoursV2($thisWeek);
        $weekBeforeValues = $habitService->convertSecondsToMinutesOrHoursV2($weekBefore);

        $percentages = $this->calculatePercentageDifferenceBetweenTwoNumbers($thisWeek, $weekBefore);

        // The difference between the two numbers in minutes
        $minuteDifference = round(abs($weekBefore - $thisWeek) / 60);


        return [
            'description' => 'Last month, you did ' . $minuteDifference . ' ' . ($weekBefore < $thisWeek ? "more" : "fewer") . ' minutes in total than the week before.',
            'barOne' => [
                "number" => $thisWeekValues['value'],
                "unit" => $thisWeekValues['unit_full'],
                "bar_text" => $startOfRangeThisMonth->format('j. F') . ' - ' . $endOfRangeThisMonth->format('j. F'),
                "width" => $percentages[0]
            ],
            'barTwo' => [
                "number" => $weekBeforeValues['value'],
                "unit" => $weekBeforeValues['unit_full'],
                "bar_text" => $startOfRangeLastMonth->format('j. F') . ' - ' . $endOfRangeLastMonth->format('j. F'),
                "width" => $percentages[1]
            ]
        ];
    }

    /**
     * Get Yearly Summary highlights
     *
     */
    public function getYearlySummaryHighlights(HabitUser $habitUser, string $timezone = 'UTC', HabitService $habitService, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $startOfRangeThisYear = Carbon::now($timezone)->startOfYear()->setTimezone('UTC');
        $endOfRangeThisYear = Carbon::now($timezone)->endOfYear()->setTimezone('UTC');

        $startOfRangeLastYear = Carbon::now($timezone)->subYears(1)->startOfYear()->setTimezone('UTC');
        $endOfRangeLastYear = Carbon::now($timezone)->subYears(1)->endOfYear()->setTimezone('UTC');

        $thisYear = $habitInsightRepository->getSummationByHabitId($habitUser->user_id, $habitIds, $startOfRangeThisYear, $endOfRangeThisYear);
        $lastYear = $habitInsightRepository->getSummationByHabitId($habitUser->user_id, $habitIds, $startOfRangeLastYear, $endOfRangeLastYear);

        // Convert values to hours/minutes
        $thisYearValues = $habitService->convertSecondsToMinutesOrHoursV2($thisYear);
        $lastYearValues = $habitService->convertSecondsToMinutesOrHoursV2($lastYear);

        // calculate percentages
        $percentages = $this->calculatePercentageDifferenceBetweenTwoNumbers($thisYear, $lastYear);

        return [
            'description' => 'This year, you did ' . round(abs($lastYear - $thisYear) / 60) . ' ' . ($lastYear < $thisYear ? "more" : "fewer") . ' minutes in total than the year before',
            'barOne' => [
                "number" => $thisYearValues['value'],
                "unit" => $thisYearValues['unit_full'],
                "bar_text" => $startOfRangeThisYear->format('Y'),
                "width" => $percentages[0],
            ],
            'barTwo' => [
                "number" => $lastYearValues['value'],
                "unit" => $lastYearValues['unit_full'],
                "bar_text" => $startOfRangeLastYear->format('Y'),
                "width" => $percentages[1],
            ],
        ];
    }

    /**
     * Calculate percentage difference between two numbers
     *
     * @param integer $value1
     * @param integer $value2
     * @return array
     */
    private function calculatePercentageDifferenceBetweenTwoNumbers(int $value1, int $value2): array
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
     * Get Streaks
     *
     * @param HabitUser $habitUser
     * @param integer $userId
     * @param string $timezone
     * @param integer $habitId
     * @param HabitService $habitService
     * @param HabitInsightRepository $habitInsightRepository
     * @return array
     */
    public function getStreaks(HabitUser $habitUser, int $userId, string $timezone = 'UTC', int $habitId, HabitInsightRepository $habitInsightRepository): array
    {
        // Not a goal oriented habit, then bail early
        if (empty($habitUser->streak_time_goal)) {
            return [];
        }

        if ($habitUser->streak_time_type === 'weekly') {
            return $this->getWeeklyStreaks($habitUser, $userId, $timezone, $habitId, $habitInsightRepository);
        }

        return $this->getDailyStreaks($habitUser, $userId, $timezone, $habitId, $habitInsightRepository);
    }

    /**
     * Get weekly streaks
     *
     * @param HabitUser $habitUser
     * @param integer $userId
     * @param string $timezone
     * @param integer $habitId
     * @param HabitInsightRepository $habitInsightRepository
     * @return array
     */
    public function getWeeklyStreaks(HabitUser $habitUser, int $userId, string $timezone = 'UTC', int $habitId, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $weeklyTotals = $habitInsightRepository->getWeeklyTotalsByHabitId($userId, $timezone, $habitIds);

        $currentStreakCount = 0;
        $longestStreakCount = 0;
        $totalStreaks = 0;
        $previousWeek = null;
        $thisWeek = Carbon::now($timezone)->startOfWeek()->setTimezone('UTC');

        foreach ($weeklyTotals as $weeklyTotal) {
            $year = substr($weeklyTotal->week, 0, 4);
            $week = substr($weeklyTotal->week, 4, 2);
            $currentWeek = Carbon::createFromDate($year, null, null, $timezone)->setISODate($year, $week)->startOfWeek()->setTimezone('UTC');

            if ($previousWeek && $previousWeek->eq($currentWeek->copy()->subWeek()) && $weeklyTotal->total_duration >= $habitUser->streak_time_goal) {
                $currentStreakCount++;  // Continue the streak
            } else {
                // If current week is the same as this week, we are continuing the streak.
                if (!$currentWeek->eq($thisWeek)) {
                    $currentStreakCount = ($weeklyTotal->total_duration >= $habitUser->streak_time_goal) ? 1 : 0;
                }
            }

            // Check if the current streak is the longest streak.
            $longestStreakCount = max($longestStreakCount, $currentStreakCount);

            // If the weekly goal is met, increment the total streaks.
            if ($weeklyTotal->total_duration >= $habitUser->streak_time_goal) {
                $totalStreaks++;
            }

            $previousWeek = $currentWeek;
        }

        return [
            'goals' => ($habitUser->streak_time_goal / 60) . ' minutes',
            'goalsType' => 'Weeks',
            'currentStreak' => $currentStreakCount,
            'longestStreak' => $longestStreakCount,
            'totalStreaks' => $totalStreaks,
        ];
    }



    /**
     * Get Daily Straeks
     *
     * @param HabitUser $habitUser
     * @param integer $userId
     * @param string $timezone
     * @param integer $habitId
     * @param HabitInsightRepository $habitInsightRepository
     * @return array
     */
    public function getDailyStreaks(HabitUser $habitUser, int $userId, string $timezone = 'UTC',  int $habitId, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $dailyTotals = $habitInsightRepository->getDailyTotalsByHabitId($userId, $timezone, $habitIds);
        $currentStreakCount = 0;
        $longestStreakCount = 0;
        $totalStreaks = 0;
        $previousDate = null;
        $today = Carbon::today($timezone)->setTimezone('UTC');

        foreach ($dailyTotals as $dailyTotal) {
            $currentDate = Carbon::parse($dailyTotal->date);

            if ($previousDate && $previousDate->eq($currentDate->copy()->subDay(1)) && $dailyTotal->total_duration >= $habitUser->streak_time_goal) {
                // We are continuing the streak.
                $currentStreakCount++;
            } else {
                // This is either a start of a new streak or a day that doesn't meet the streak criteria.
                if (!$currentDate->eq($today)) {
                    // Reset the current streak count if the streak is broken or start a new streak if the goal is met.
                    $currentStreakCount = ($dailyTotal->total_duration >= $habitUser->streak_time_goal) ? 1 : 0;
                }
            }

            // Update the longest streak if the current streak exceeds it.
            $longestStreakCount = max($longestStreakCount, $currentStreakCount);

            // Increment totalStreaks if the streak_time_goal is met for this day.
            if ($dailyTotal->total_duration >= $habitUser->streak_time_goal) {
                $totalStreaks++;
            }

            // Set the current date as the previous date for the next iteration.
            $previousDate = $currentDate;
        }


        return [
            'goals' => ($habitUser->streak_time_goal / 60) . ' minutes',
            'goalsType' => 'Days',
            'currentStreak' => $currentStreakCount,
            'longestStreak' => $longestStreakCount,
            'totalStreaks' => $totalStreaks,
        ];
    }

    /**
     * Loop through the habits and check if any of them are active
     *
     * @param array $habitIds
     * @param integer $userId
     * @param HabitInsightRepository $habitInsightRepository
     * @return bool
     */
    public function checkIfHabitsAreActive(array $habitIds, int $userId, HabitInsightRepository $habitInsightRepository): bool
    {
        foreach ($habitIds as $habitId) {
            $isHabitActive = $habitInsightRepository->isHabitActive($habitId, $userId);
            if ($isHabitActive) {
                return true;
            }
        }

        return false;
    }

    /**
     * Group habit ids. If it's a parent habit, then we need to include the children habit ids as well.
     *
     * @param HabitUser $habitUser
     * @return array
     */
    private function fetchHabitIdsBasedOnHierarchy(HabitUser $habitUser): array
    {
        $habitIds = [$habitUser->habit_id];
        if ($habitUser->children) {
            $habitIds = array_merge($habitIds, $habitUser->children->pluck('habit_id')->toArray());
        }

        return $habitIds;
    }

    /**
     * Determine if it's a daily or weekly habit and fetch the totals accordingly.
     *
     */
    private function fetchTotalDurationBasedOnStreakType(HabitUser $habit, int $userId, string $timezone = 'UTC', array $habitIds, array $dateRanges, HabitInsightRepository $insightRepository)
    {
        if ($habit->streak_time_type === 'weekly') {
            $totals = $insightRepository->getWeeklyTotalsByHabitId($userId, $timezone, $habitIds, $dateRanges['weekly']['start'], $dateRanges['weekly']['end']);
        } else {
            $totals = $insightRepository->getDailyTotalsByHabitId($userId, $timezone, $habitIds, $dateRanges['daily']['start'], $dateRanges['daily']['end']);
        }

        return $totals->sum('total_duration');
    }

    /**
     * Convert time to summary page format
     *
     * @param HabitService $service
     * @param integer $time
     * @return array
     */
    private function convertTimeToSummaryPageFormat(HabitService $service, int $time): array
    {
        $convertedTime = $service->convertSecondsToMinutesOrHoursV2($time);

        return [
            'total' => $convertedTime['value'],
            'unit' => $convertedTime['unit'],
        ];
    }

    /**
     * Convert goal timet o summary page format
     *
     * @param HabitService $service
     * @param HabitUser $habit
     * @return array
     */
    private function convertGoalTimeToSummaryPageFormat(HabitService $service, HabitUser $habit): array
    {
        // Check if it's a goal type habit, some aren't.
        if (isset($habit->streak_time_goal)) {
            $convertedGoalTime = $service->convertSecondsToMinutesOrHoursV2($habit->streak_time_goal);
            return [
                'total' => $convertedGoalTime['value'],
                'unit' => $convertedGoalTime['unit'],
                'type' => $habit->streak_time_type,
            ];
        }

        return ['total' => null, 'unit' => null, 'type' => $habit->streak_time_type];
    }
}
