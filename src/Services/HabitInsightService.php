<?php

namespace NickKlein\Habits\Services;

use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use NickKlein\Habits\Services\HabitTypeFactory;

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
        17 => 'rose',
        18 => 'rose',
        19 => 'cyan',
        20 => 'sky',
        21 => 'emerald',
        22 => 'violet',
    ];

    const GOAL_PERIOD_DAILY = 'daily';
    const GOAL_PERIOD_WEEKLY = 'weekly';

    public function __construct(private HabitTypeFactory $habitTypeFactory)
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
            self::GOAL_PERIOD_DAILY => [
                'start' => Carbon::today($timezone)->startOfDay()->setTimezone('UTC'),
                'end' => Carbon::today($timezone)->endOfDay()->setTimezone('UTC'),
            ],
            self::GOAL_PERIOD_WEEKLY => [
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

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);

        $currentValue = $handler->formatValue($time);
        $goalValue = $handler->formatGoal($habitUser);

        return [
            'id' => $habitUser->habit_id,
            'name' => $habitUser->habit->name,
            'current' => [
                'total' => $currentValue['value'],
                'unit' => $currentValue['unit'],
            ],
            'goal' => $goalValue,
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
            /*->whereIn('habit_id', [10, 11, 5, 9, 14, 15, 16, 19, 8, 4])*/
            ->whereNotNull('streak_goal')
            ->orderBy('streak_time_type', 'ASC')
            ->get();
        $notification = '';
        $insightsRepository = app(HabitInsightRepository::class);
        $startOfDay = Carbon::today($timezone)->startOfDay()->setTimezone('UTC');
        $endOfDay = Carbon::today($timezone)->endOfDay()->setTimezone('UTC');
        $startOfWeek = Carbon::today($timezone)->startOfWeek()->setTimezone('UTC');
        $endOfWeek = Carbon::today($timezone)->endOfWeek()->setTimezone('UTC');

        foreach($habitUser as $item) {
            // TODO: Convert this to use HabitTypeFactory
            $habitIdsArray = $this->fetchHabitIdsBasedOnHierarchy($item);
            $handler = $this->habitTypeFactory->getHandler($item->habit_type);

            if ($item->streak_time_type === self::GOAL_PERIOD_DAILY) {
                $dailyTotals = $insightsRepository->getDailyTotalsByHabitId($userId, $timezone, $habitIdsArray, $startOfDay, $endOfDay);
                // if the total duration is higher than the goal, then don't show in the notification
                if ($dailyTotals->first() && $item->streak_goal < $dailyTotals->first()->total_duration) {
                    continue;
                }

                $name = $item->habit->name;
                $formattedGoalValues = $handler->formatValue($item->streak_goal);
                $formattedTotalValues = $handler->formatValue($dailyTotals->sum('total_duration'));
                $streakSummary[] = [
                    'name' => $name,
                    'type' => 'd',
                    'goal' => $formattedGoalValues['value'] . $formattedGoalValues['unit'],
                    'total' => $formattedTotalValues['value'] . $formattedTotalValues['unit'],
                ];
            }

            if ($item->streak_time_type === self::GOAL_PERIOD_WEEKLY) {
                $weeklyTotals = $insightsRepository->getWeeklyTotalsByHabitId($userId, $timezone, $habitIdsArray, $startOfWeek, $endOfWeek);
                // if the total duration is higher than the goal, then don't show in the notification
                if ($weeklyTotals->first() && $item->streak_goal < $weeklyTotals->first()->total_duration) {
                    continue;
                }

                $name = $item->habit->name;
                $formattedGoalValues = $handler->formatValue($item->streak_goal);
                $formattedTotalValues = $handler->formatValue($weeklyTotals->sum('total_duration'));

                $streakSummary[] = [
                    'name' => $name,
                    'type' => 'w',
                    'goal' => $formattedGoalValues['value'] . $formattedGoalValues['unit'],
                    'total' => $formattedTotalValues['value'] . $formattedTotalValues['unit'],
                ];
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

            // TODO: Convert this use factory
            $thisWeekAvg = $habitService->convertSecondsToMinutesOrHours($thisWeek);
            $percentageDifference = $habitService->percentageDifference($weekBefore, $thisWeek);

            $notification .= $name . ': ' . $thisWeekAvg . ' (' . $percentageDifference . '%), ';
        }

        return $notification;
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

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);
        $yesterdayValues = $handler->formatValue($yesterdayCollection->sum('total_duration'));
        $dayOfBeforeYesterdayValues = $handler->formatValue($dayOfBeforeYesterdayCollection->sum('total_duration'));

        $percentages = $handler->calculatePercentageDifference($yesterdayCollection->sum('total_duration'), $dayOfBeforeYesterdayCollection->sum('total_duration'));

        // The difference between the two numbers in minutes
        $difference = $handler->formatDifference($yesterdayCollection->sum('total_duration'), $dayOfBeforeYesterdayCollection->sum('total_duration'));

        // Get units
        $unit = $handler->getUnitLabelFull();
        $differenceQualifier = $yesterdayCollection->sum('total_duration') > $dayOfBeforeYesterdayCollection->sum('total_duration') ? 'more' : 'less';


        return [
            'description' => "You did {$difference} {$differenceQualifier} {$unit} yesterday than you did the day before",
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

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);

        $thisWeekValues = $handler->formatValue($thisWeek);
        $weekBeforeValues = $handler->formatValue($weekBefore);

        $percentages = $handler->calculatePercentageDifference($thisWeek, $weekBefore);

        $unit = $handler->getUnitLabelFull();
        // The difference between the two numbers in minutes
        $difference = $handler->formatDifference($weekBefore, $thisWeek);
        $differenceQualifier = $weekBefore < $thisWeek ? 'more' : 'less';

        return [
            'description' => "Last week, you averaged {$difference} {$differenceQualifier} {$unit} than the week before",
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
        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);
        $thisWeekValues = $handler->formatValue($thisWeek);
        $weekBeforeValues = $handler->formatValue($weekBefore);

        $percentages = $handler->calculatePercentageDifference($thisWeek, $weekBefore);

        // The difference between the two numbers in minutes
        $difference = $handler->formatDifference($weekBefore, $thisWeek);
        $differenceQualifier = $weekBefore < $thisWeek ? "more" : "fewer";
        $unit = $handler->getUnitLabelFull();

        return [
            'description' => "Last week, you did {$difference} {$differenceQualifier} {$unit} in total than the week before",
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

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);
        $thisMonthValues = $handler->formatValue($thisMonth);
        $lastMonthValues = $handler->formatValue($lastMonth);

        // calculate percentages
        $percentages = $handler->calculatePercentageDifference($thisMonth, $lastMonth);
        $difference = $handler->formatDifference($lastMonth, $thisMonth);
        $differenceQualifier = $lastMonth < $thisMonth ? "more" : "fewer";
        $unit = $handler->getUnitLabelFull();

        //TODO: bar_text still needs to be updated using the factory
        return [
            'description' => "Last month, you averaged {$difference} {$differenceQualifier} {$unit} than the month before.",
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

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);
        $thisWeekValues = $handler->formatValue($thisWeek);
        $weekBeforeValues = $handler->formatValue($weekBefore);

        $percentages = $handler->calculatePercentageDifference($thisWeek, $weekBefore);

        // The difference between the two numbers in minutes
        $difference = $handler->formatDifference($weekBefore, $thisWeek);
        $differenceQualifer = $weekBefore < $thisWeek ? "more" : "fewer";
        $unit = $handler->getUnitLabelFull();


        return [
            'description' => "Last month, you did {$difference} {$differenceQualifer} {$unit} in total than the week before.",
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

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);
        $thisYearValues = $handler->formatValue($thisYear);
        $lastYearValues = $handler->formatValue($lastYear);

        $percentages = $handler->calculatePercentageDifference($thisYear, $lastYear);
        $difference = $handler->formatDifference($lastYear, $thisYear);
        $differenceQualifer = $lastYear < $thisYear ? "more" : "fewer";
        $unit = $handler->getUnitLabelFull();

        return [
            'description' => "This year, you did {$difference} {$differenceQualifer} {$unit} in total than the year before",
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
        if (empty($habitUser->streak_goal)) {
            return [];
        }

        if ($habitUser->streak_time_type === self::GOAL_PERIOD_WEEKLY) {
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

            if ($previousWeek && $previousWeek->eq($currentWeek->copy()->subWeek()) && $weeklyTotal->total_duration >= $habitUser->streak_goal) {
                $currentStreakCount++;  // Continue the streak
            } else {
                // If current week is the same as this week, we are continuing the streak.
                if (!$currentWeek->eq($thisWeek)) {
                    $currentStreakCount = ($weeklyTotal->total_duration >= $habitUser->streak_goal) ? 1 : 0;
                }
            }

            // Check if the current streak is the longest streak.
            $longestStreakCount = max($longestStreakCount, $currentStreakCount);

            // If the weekly goal is met, increment the total streaks.
            if ($weeklyTotal->total_duration >= $habitUser->streak_goal) {
                $totalStreaks++;
            }

            $previousWeek = $currentWeek;
        }

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);
        $goalFormattedValues = $handler->formatValue($habitUser->streak_goal);

        return [
            'goals' => $goalFormattedValues['value'] . ' ' . $goalFormattedValues['unit_full'],
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

            if ($previousDate && $previousDate->eq($currentDate->copy()->subDay(1)) && $dailyTotal->total_duration >= $habitUser->streak_goal) {
                // We are continuing the streak.
                $currentStreakCount++;
            } else {
                // This is either a start of a new streak or a day that doesn't meet the streak criteria.
                if (!$currentDate->eq($today)) {
                    // Reset the current streak count if the streak is broken or start a new streak if the goal is met.
                    $currentStreakCount = ($dailyTotal->total_duration >= $habitUser->streak_goal) ? 1 : 0;
                }
            }

            // Update the longest streak if the current streak exceeds it.
            $longestStreakCount = max($longestStreakCount, $currentStreakCount);

            // Increment totalStreaks if the streak_goal is met for this day.
            if ($dailyTotal->total_duration >= $habitUser->streak_goal) {
                $totalStreaks++;
            }

            // Set the current date as the previous date for the next iteration.
            $previousDate = $currentDate;
        }

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);
        $goalFormattedValues = $handler->formatValue($habitUser->streak_goal);

        return [
            'goals' => $goalFormattedValues['value'] . ' ' . $goalFormattedValues['unit_full'],
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
        if ($habit->streak_time_type === self::GOAL_PERIOD_WEEKLY) {
            $totals = $insightRepository->getWeeklyTotalsByHabitId($userId, $timezone, $habitIds, $dateRanges[self::GOAL_PERIOD_WEEKLY]['start'], $dateRanges[self::GOAL_PERIOD_WEEKLY]['end']);

            return $totals->sum('total_duration');
        }
        $totals = $insightRepository->getDailyTotalsByHabitId($userId, $timezone, $habitIds, $dateRanges[self::GOAL_PERIOD_DAILY]['start'], $dateRanges[self::GOAL_PERIOD_DAILY]['end']);

        return $totals->sum('total_duration');
    }
}
