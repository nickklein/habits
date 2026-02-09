<?php

namespace NickKlein\Habits\Services;

use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use NickKlein\Habits\Services\HabitTypeFactory;
use NickKlein\Habits\Enums\GoalPeriodEnum;

class HabitInsightService
{
    // @deprecated
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


    public function __construct(private HabitTypeFactory $habitTypeFactory)
    {
        //
    }

    public function charts(HabitUser $habitUser, int $userId, string $timezone, int $habitId, HabitInsightRepository $insightRepository): array
    {
        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);

        // Define date range
        $period = CarbonPeriod::create(
            Carbon::now($timezone)->subDays(30)->startOfDay(),
            Carbon::now($timezone)->endOfDay()
        );

        // Get actual data
        $data = $insightRepository->getDailyTotalsByHabitId(
            $userId,
            $timezone,
            [$habitId],
            $period->start->copy()->setTimezone('UTC'),
            $period->end->copy()->setTimezone('UTC')
        )->keyBy('date_column');

        // Map all dates with actual or zero values
        return collect($period)->map(function($date) use ($data, $handler) {
            $dateKey = $date->format('Y-m-d');
            $duration = $data->get($dateKey)?->total_duration ?? 0;
            $formatted = $handler->formatValueForChart($duration);

            return [
                'date_column' => $date->format('M d'),
                'total_duration' => $formatted['value'],
                'unit' => $formatted['unit'],
            ];
        })->values()->all();
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
            GoalPeriodEnum::DAILY->value => [
                'start' => Carbon::today($timezone)->startOfDay()->setTimezone('UTC'),
                'end' => Carbon::today($timezone)->endOfDay()->setTimezone('UTC'),
            ],
            GoalPeriodEnum::WEEKLY->value => [
                'start' => Carbon::now($timezone)->startOfWeek()->startOfDay()->setTimezone('UTC'),
                'end' => Carbon::now($timezone)->endOfWeek()->endOfDay()->setTimezone('UTC'),
            ]
        ];
        $group = 0;
        $summaries = [];
        foreach ($habitsUser as $key => $habit) {
            $color = $habit->color_index ?? 'black';
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

    /**
     * Get single habit user summary for AJAX calls
     *
     * @param HabitUser $habitUser
     * @param string $timezone
     * @param HabitService $service
     * @param HabitInsightRepository $insightRepository
     * @param string|null $selectedDate (Y-m-d format, defaults to today)
     * @return array
     */
    public function getSingleHabitSummary(HabitUser $habitUser, string $page, string $timezone, HabitService $service, HabitInsightRepository $insightRepository, string $selectedDate = null): array
    {
        $date = $selectedDate 
            ? Carbon::createFromFormat('Y-m-d', $selectedDate, $timezone)->startOfDay()
            : Carbon::today($timezone);
            
        $dateRanges = [
            GoalPeriodEnum::DAILY->value => [
                'start' => $date->copy()->startOfDay()->setTimezone('UTC'),
                'end' => $date->copy()->endOfDay()->setTimezone('UTC'),
            ],
            GoalPeriodEnum::WEEKLY->value => [
                'start' => $date->copy()->startOfWeek()->setTimezone('UTC'),
                'end' => $date->copy()->endOfDay()->setTimezone('UTC'),
            ]
        ];

        $color = $habitUser->color_index ?? '#ffffff';
        $summary = $this->generateDailySummariesForUser($habitUser, $habitUser->user_id, $page, $timezone, $dateRanges, $color, $service, $insightRepository);

        // Handle children if they exist
        if ($habitUser->children && $habitUser->children->count() > 0) {
            $summary['children'] = [];
            foreach ($habitUser->children as $child) {
                $summary['children'][] = $this->generateDailySummariesForUser($child, $habitUser->user_id, $page, $timezone, $dateRanges, $color, $service, $insightRepository);
            }
        }

        return $summary;
    }


    public function generateDailySummariesForUser(HabitUser $habitUser, int $userId, string $page, string $timezone, array $dateRanges, string $color, HabitService $service, HabitInsightRepository $insightRepository)
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);
        $unitValue = $this->fetchTotalDurationBasedOnStreakType($habitUser, $userId, $timezone, $habitIds, $dateRanges, $insightRepository);

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);

        $currentValue = $handler->formatValue($unitValue);
        $goalValue = $handler->formatGoal($habitUser);

        $isActive = $insightRepository->isHabitActive($habitUser->habit_id, $userId);
        $activeElapsedSeconds = $isActive ? $insightRepository->getActiveHabitElapsedSeconds($habitUser->habit_id, $userId, $timezone) : null;

        return [
            'id' => $habitUser->habit_id,
            'name' => $habitUser->habit->name,
            'icon' => $habitUser->icon,
            'current' => [
                'total' => $currentValue['value'],
                'unit' => $currentValue['unit'],
            ],
            'goal' => $goalValue,
            'color_index' => $color,
            'goal_met' => $unitValue >= $habitUser->streak_goal,
            'is_active' => $isActive,
            'active_elapsed_seconds' => $activeElapsedSeconds,
            'url' => $page === 'insights' ? route('habits.show', $habitUser->habit_id) : route('habits.add-transaction', $habitUser->habit_id),
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
            ->orderBy('streak_time_type')
            ->orderBy('streak_goal')
            ->where('archive', false)
            ->get();

        $insightsRepository = app(HabitInsightRepository::class);
        $startOfDay = Carbon::today($timezone)->startOfDay()->setTimezone('UTC');
        $endOfDay = Carbon::today($timezone)->endOfDay()->setTimezone('UTC');
        $startOfWeek = Carbon::today($timezone)->startOfWeek()->startOfDay()->setTimezone('UTC');
        $endOfWeek = Carbon::today($timezone)->endOfWeek()->endOfDay()->setTimezone('UTC');

        foreach($habitUser as $item) {
            // TODO: Convert this to use HabitTypeFactory
            $habitIdsArray = $this->fetchHabitIdsBasedOnHierarchy($item);
            $handler = $this->habitTypeFactory->getHandler($item->habit_type);

            if ($item->streak_time_type === GoalPeriodEnum::DAILY->value) {
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

            if ($item->streak_time_type === GoalPeriodEnum::WEEKLY->value) {
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

        $startOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->subDays(6)->startOfDay()->setTimezone('UTC');
        $endOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->endOfDay()->setTimezone('UTC');

        $startOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->subDays(6)->startOfDay()->setTimezone('UTC');
        $endOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->endOfDay()->setTimezone('UTC');

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

        $yesterday = Carbon::yesterday($timezone)->startOfDay()->setTimezone('UTC');
        $yesterdayEnd = Carbon::yesterday($timezone)->endOfDay()->setTimezone('UTC');

        $dayBeforeYesterday = Carbon::today($timezone)->subDays(2)->startOfDay()->setTimezone('UTC');
        $dayBeforeYesterdayEnd = Carbon::today($timezone)->subDays(2)->endOfDay()->setTimezone('UTC');

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

        $startOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->subDays(6)->startOfDay()->setTimezone('UTC');
        $endOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->endOfDay()->setTimezone('UTC');

        $startOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->subDays(6)->startOfDay()->setTimezone('UTC');
        $endOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->endOfDay()->setTimezone('UTC');

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
                "bar_text" => $startOfRangeThisWeek->setTimezone($timezone)->format('j. F') . ' - ' . $endOfRangeThisWeek->setTimezone($timezone)->format('j. F'),
                "width" => $percentages[0]
            ],
            'barTwo' => [
                "number" => $weekBeforeValues['value'],
                "unit" => $weekBeforeValues['unit_full'] . ' / day',
                "bar_text" => $startOfRangeLastWeek->setTimezone($timezone)->format('j. F') . ' - ' . $endOfRangeLastWeek->setTimezone($timezone)->format('j. F'),
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

        $startOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->subDays(6)->startOfDay()->setTimezone('UTC');
        $endOfRangeThisWeek = Carbon::now($timezone)->subWeeks(0)->endOfDay()->setTimezone('UTC');

        $startOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->subDays(6)->startOfDay()->setTimezone('UTC');
        $endOfRangeLastWeek = Carbon::now($timezone)->subWeeks(1)->endOfDay()->setTimezone('UTC');

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
                "bar_text" => $startOfRangeThisWeek->setTimezone($timezone)->format('j. F') . ' - ' . $endOfRangeThisWeek->setTimezone($timezone)->format('j. F'),
                "width" => $percentages[0]
            ],
            'barTwo' => [
                "number" => $weekBeforeValues['value'],
                "unit" => $weekBeforeValues['unit_full'],
                "bar_text" => $startOfRangeLastWeek->setTimezone($timezone)->format('j. F') . ' - ' . $endOfRangeLastWeek->setTimezone($timezone)->format('j. F'),
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

        $startOfRangeThisMonth = Carbon::now($timezone)->subMonths(1)->startOfDay()->setTimezone('UTC');
        $endOfRangeThisMonth = Carbon::now($timezone)->endOfDay()->setTimezone('UTC');

        $startOfRangeLastMonth = Carbon::now($timezone)->subMonths(2)->startOfDay()->setTimezone('UTC');
        $endOfRangeLastMonth = Carbon::now($timezone)->subMonths(1)->endOfDay()->setTimezone('UTC');

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
                "bar_text" => $startOfRangeThisMonth->setTimezone($timezone)->format('j. F') . ' - ' . $endOfRangeThisMonth->setTimezone($timezone)->format('j. F'),
                "width" => $percentages[0],
            ],
            'barTwo' => [
                "number" => $lastMonthValues['value'],
                "unit" => $lastMonthValues['unit_full'] . ' / day',
                "bar_text" => $startOfRangeLastMonth->setTimezone($timezone)->format('j. F') . ' - ' . $endOfRangeLastMonth->setTimezone($timezone)->format('j. F'),
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

        $startOfRangeThisMonth = Carbon::now($timezone)->subMonths(1)->startOfDay()->setTimezone('UTC');
        $endOfRangeThisMonth = Carbon::now($timezone)->endOfDay()->setTimezone('UTC');

        $startOfRangeLastMonth = Carbon::now($timezone)->subMonths(2)->startOfDay()->setTimezone('UTC');
        $endOfRangeLastMonth = Carbon::now($timezone)->subMonths(1)->endOfDay()->setTimezone('UTC');

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
                "bar_text" => $startOfRangeThisMonth->setTimezone($timezone)->format('j. F') . ' - ' . $endOfRangeThisMonth->setTimezone($timezone)->format('j. F'),
                "width" => $percentages[0]
            ],
            'barTwo' => [
                "number" => $weekBeforeValues['value'],
                "unit" => $weekBeforeValues['unit_full'],
                "bar_text" => $startOfRangeLastMonth->setTimezone($timezone)->format('j. F') . ' - ' . $endOfRangeLastMonth->setTimezone($timezone)->format('j. F'),
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

        $startOfRangeThisYear = Carbon::now($timezone)->startOfYear()->startOfDay()->setTimezone('UTC');
        $endOfRangeThisYear = Carbon::now($timezone)->endOfYear()->setTimezone('UTC');

        $startOfRangeLastYear = Carbon::now($timezone)->subYears(1)->startOfYear()->startOfDay()->setTimezone('UTC');
        $endOfRangeLastYear = Carbon::now($timezone)->subYears(1)->endOfYear()->endOfDay()->setTimezone('UTC');

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
     * Get Yearly Summary highlights
     *
    */
    public function getTotalSummaryHighlights(HabitUser $habitUser, string $timezone = 'UTC', HabitService $habitService, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $startOfJourney = Carbon::now($timezone)->subYear(25)->startOfDay()->setTimezone('UTC');
        $currentDate = Carbon::now($timezone)->endOfDay()->setTimezone('UTC');

        $totals = $habitInsightRepository->getSummationByHabitId($habitUser->user_id, $habitIds, $startOfJourney, $currentDate);

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);
        $totalValues = $handler->formatValue($totals);

        $unit = $handler->getUnitLabelFull();

        return [
            'description' => "You have done {$totals} {$unit} in total to date.",
            'barOne' => [
                "number" => $totalValues['value'],
                "unit" => $totalValues['unit_full'],
                "bar_text" => 'Total',
                "width" => 100,
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

        if ($habitUser->streak_time_type === GoalPeriodEnum::WEEKLY->value) {
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
        $thisWeek = Carbon::now($timezone)->startOfWeek()->startOfDay()->setTimezone('UTC');

        foreach ($weeklyTotals as $weeklyTotal) {
            $year = substr($weeklyTotal->week, 0, 4);
            $week = substr($weeklyTotal->week, 4, 2);
            $currentWeek = Carbon::createFromDate($year, null, null, $timezone)->setISODate($year, $week)->startOfWeek()->startOfDay()->setTimezone('UTC');

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
        $today = Carbon::today($timezone)->startOfDay()->setTimezone('UTC');

        foreach ($dailyTotals as $dailyTotal) {
            $currentDate = Carbon::parse($dailyTotal->date_column);

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
        if ($habit->streak_time_type === GoalPeriodEnum::WEEKLY->value) {
            $totals = $insightRepository->getWeeklyTotalsByHabitId($userId, $timezone, $habitIds, $dateRanges[GoalPeriodEnum::WEEKLY->value]['start'], $dateRanges[GoalPeriodEnum::WEEKLY->value]['end']);

            return $totals->sum('total_duration');
        }
        $totals = $insightRepository->getDailyTotalsByHabitId($userId, $timezone, $habitIds, $dateRanges[GoalPeriodEnum::DAILY->value]['start'], $dateRanges[GoalPeriodEnum::DAILY->value]['end']);

        return $totals->sum('total_duration');
    }

    public function getTagBreakdown(HabitUser $habitUser, string $timezone, HabitInsightRepository $habitInsightRepository): array
    {
        $habitIds = $this->fetchHabitIdsBasedOnHierarchy($habitUser);

        $startOfYear = Carbon::now($timezone)->startOfYear()->startOfDay()->setTimezone('UTC');
        $endOfYear = Carbon::now($timezone)->endOfYear()->endOfDay()->setTimezone('UTC');

        $breakdown = $habitInsightRepository->getTagBreakdownByHabitId($habitUser->user_id, $habitIds, $startOfYear, $endOfYear);

        $handler = $this->habitTypeFactory->getHandler($habitUser->habit_type);

        return $breakdown->map(function ($row) use ($handler) {
            $formatted = $handler->formatValue($row->total_duration);
            return [
                'name' => $row->tag_name,
                'value' => (float) $row->total_duration,
                'formatted' => $formatted['value'] . ' ' . $formatted['unit_full'],
            ];
        })->values()->toArray();
    }

    public function yearlyComparisonChartForHabit(int $habitId, int $userId, string $timezone, HabitInsightRepository $insightRepository): array
    {
        $currentYear = Carbon::now($timezone)->year;
        $previousYear = $currentYear - 1;

        $yearlyData = [];

        foreach ([$previousYear, $currentYear] as $year) {
            $startDate = Carbon::createFromDate($year, 1, 1, $timezone)->startOfDay()->setTimezone('UTC');
            $endDate = Carbon::createFromDate($year, 12, 31, $timezone)->endOfDay()->setTimezone('UTC');

            $dailyTotals = $insightRepository->getDailyTotalsByHabitId($userId, $timezone, [$habitId], $startDate, $endDate);

            $cumulativeTotal = 0;
            $cumulativeData = [];

            $currentDate = Carbon::createFromDate($year, 1, 1, $timezone);
            $lastDate = min(Carbon::now($timezone), Carbon::createFromDate($year, 12, 31, $timezone));

            $dailyTotalsMap = [];
            foreach ($dailyTotals as $dailyTotal) {
                $dailyTotalsMap[$dailyTotal->date_column] = $dailyTotal->total_duration;
            }

            while ($currentDate <= $lastDate) {
                $dateKey = $currentDate->format('Y-m-d');
                $dailyDuration = $dailyTotalsMap[$dateKey] ?? 0;
                $cumulativeTotal += $dailyDuration;

                $cumulativeData[$currentDate->dayOfYear] = round($cumulativeTotal / 3600, 2);

                $currentDate->addDay();
            }

            $yearlyData[$year] = $cumulativeData;
        }

        // Combine the data into the format expected by Recharts
        $combinedData = [];
        $maxDays = max(count($yearlyData[$previousYear]), count($yearlyData[$currentYear]));

        for ($day = 1; $day <= $maxDays; $day++) {
            $entry = ['day_of_year' => $day];

            if (isset($yearlyData[$previousYear][$day])) {
                $entry["total_$previousYear"] = $yearlyData[$previousYear][$day];
            }

            if (isset($yearlyData[$currentYear][$day])) {
                $entry["total_$currentYear"] = $yearlyData[$currentYear][$day];
            }

            $combinedData[] = $entry;
        }

        return [
            'data' => $combinedData,
            'years' => [
                'previous' => $previousYear,
                'current' => $currentYear
            ]
        ];
    }
}
