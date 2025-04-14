<?php

namespace NickKlein\Habits\Listeners;

use App\Services\LogsService;
use App\Services\PushoverService;
use Carbon\Carbon;
use NickKlein\Habits\Events\HabitEndedEvent;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Repositories\HabitInsightRepository;

class HabitEndListener
{
    protected array $timeRanges;
    /**
     * Create the event listener.
     */
    public function __construct(private LogsService $log, private PushoverService $pushoverService, private HabitInsightRepository $habitInsightRepository)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(HabitEndedEvent $event): void
    {

        $this->timeRanges = [
            'startOfDay' => Carbon::today($event->timezone)->startOfDay()->setTimezone('UTC'),
            'endOfDay' => Carbon::today($event->timezone)->endOfDay()->setTimezone('UTC'),
            'startOfWeek' => Carbon::today($event->timezone)->startOfWeek()->setTimezone('UTC'),
            'endOfWeek' => Carbon::today($event->timezone)->endOfWeek()->setTimezone('UTC'),
        ];

        $habitUser = HabitUser::where('user_id', $event->userId)
            ->where('habit_id', $event->habitTime->habit_id)
            ->first();
        $habitTime = $event->habitTime;

        // If there's no goal then bail
        if (empty($habitUser->streak_time_goal)) {
            return;
        }


        if ($habitUser->streak_time_type === 'daily') {
            $exists = $this->log->doesLogExistToday(['type' => 'habits.completed', 'description' => $habitUser->id], $event->timezone);
            if ($exists) {
                return;
            }
            // Get Daily Summary
            $hasAchievedDailyGoal = $this->hasAchievedDailyGoal($event->userId, $event->timezone, $habitUser->streak_time_goal, $event->habitTime->habit_id);
            if (!$hasAchievedDailyGoal) {
                return;
            }
            // Has Achieved Daily goal so send the message
            $this->sendPushOver($habitTime->habit->name);
            $this->log->handle('habits.completed', $habitUser->id);

            return;
        }

        $exists = $this->log->doesLogExistThisWeek(['type' => 'habits.completed', 'description' => $habitUser->id], $event->timezone);
        if ($exists) {
            return;
        }
        // Check if log already exists by using daily date range
        // Get Daily Summary
        $hasAchievedWeeklyGoal = $this->hasAchievedWeeklyGoal($event->userId, $event->timezone, $habitUser->streak_time_goal, $event->habitTime->habit_id);
        if (!$hasAchievedWeeklyGoal) {
            return;
        }
        // Has Achieved Daily goal so send the message
        $this->sendPushOver($habitTime->habit->name);
        $this->log->handle('habits.completed', $habitUser->id);

        return;
    }

    /**
     * Checks if the user has achieved their daily goal
     **/
    private function hasAchievedDailyGoal(int $userId, string $timezone = 'UTC', int $goalTime, int $habitId): bool
    {
        $dailyTotals = $this->habitInsightRepository->getDailyTotalsByHabitId($userId, $timezone, [$habitId], $this->timeRanges['startOfDay'], $this->timeRanges['endOfDay']);
        if ($dailyTotals->first() && $goalTime >= $dailyTotals->first()->total_duration) {
            return false;
        }

        return true;
    }


    /**
     * Checks if the user has achieved their weekly goal
     **/
    private function hasAchievedWeeklyGoal(int $userId, string $timezone = 'UTC', int $goalTime, int $habitId): bool
    {
        $weeklyTotals = $this->habitInsightRepository->getWeeklyTotalsByHabitId($userId, $timezone, [$habitId], $this->timeRanges['startOfWeek'], $this->timeRanges['endOfWeek']);
        if ($weeklyTotals->first() && $goalTime >= $weeklyTotals->first()->total_duration) {
            return false;
        }

        return true;
    }


    /**
     * Send pushoverService-
     **/
    private function sendPushOver(string $habitName): void
    {
        $this->pushoverService->send("You have completed the {$habitName} habit", 'Habit completed');
    }
}
