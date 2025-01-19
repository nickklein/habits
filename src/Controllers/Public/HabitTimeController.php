<?php

namespace NickKlein\Habits\Controllers\Public;

use Illuminate\Routing\Controller;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use NickKlein\Habits\Services\HabitInsightService;
use NickKlein\Habits\Services\HabitService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HabitTimeController extends Controller
{
    /**
     * Fetch Daily Notifications (PUBLIC API)
     * @todo obviously better to use oauth if this was a real app
     * @param integer $userId
     * @param HabitService $habitService
     * @return Response
     */
    public function getDailyNotification(int $userId, HabitInsightService $habitInsightService)
    {
        $averageTime = $habitInsightService->generateDailyNotification($userId);

        return response()->json([
            'notification' => $averageTime,
        ], Response::HTTP_OK);
    }

    /**
     * Get Weekly Notifications (PUBLIC API)
     * @todo obviously better to use oauth if this was a real app
     * @param integer $userId
     * @param HabitService $habitService
     * @return Response
     */
    public function getWeeklyNotifications(int $userId, HabitInsightService $habitInsightService)
    {
        $averageTime = $habitInsightService->generateWeeklyNotifications($userId);

        return response()->json([
            'notification' => $averageTime,
        ], Response::HTTP_OK);
    }
    /**
     * Tracks the time of a habit (PUBLIC API)
     * @todo obviously better to use oauth if this was a real app
     * @param integer $userId
     * @param integer $habitId
     * @param HabitService $habitService
     * @param HabitInsightRepository $habitInsightRepository
     * @return Response
     */
    public function store(int $userId, int $habitTimeId, string $status, HabitInsightService $habitInsightService, HabitInsightRepository $habitInsightRepository)
    {
        $response = $habitInsightService->manageHabitTime($habitTimeId, $userId, $status, $habitInsightRepository);
        if ($response) {
            return response()->json([
                'status' => 'success',
                'message' => 'Habit time added successfully',
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Habit time not added',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Ends all timers for a user (PUBLIC API)
     * @todo obviously better to use oauth if this was a real app
     * @param integer $userId
     * @param HabitInsightRepository $habitInsightRepository
     * @return void
     */
    public function endTimers(int $userId, HabitInsightRepository $habitInsightRepository)
    {
        $habitIds = request()->has('ids') ? explode(',', request()->get('ids')) : [];
        $habitInsightRepository->endAllActiveHabits($userId, $habitIds);

        return response()->json([
            'status' => 'success',
            'message' => 'Habit timers ended successfully',
        ], Response::HTTP_OK);
    }


    /**
     * Check if a habit is active (PUBLIC API)
     *  @todo obviously better to use oauth if this was a real app
     * @param integer $habitId
     * @param integer $userId
     * @param HabitInsightRepository $habitInsightRepository
     * @return boolean
     */
    public function isHabitActive(int $userId, HabitInsightService $habitInsightService, HabitInsightRepository $habitInsightRepository)
    {
        if (!request()->has('ids')) {
            return response()->json([
                'status' => 'error',
            ], Response::HTTP_NOT_FOUND);
        }

        $habitIds = explode(',', request()->get('ids'));

        $activeState = $habitInsightService->checkIfHabitsAreActive($habitIds, $userId, $habitInsightRepository);

        return response()->json([
            'status' => 'success',
            'is_active' => $activeState,
        ], Response::HTTP_OK);
    }
}
