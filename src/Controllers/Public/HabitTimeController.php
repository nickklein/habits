<?php

namespace NickKlein\Habits\Controllers\Public;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use NickKlein\Habits\Services\HabitInsightService;
use NickKlein\Habits\Services\HabitService;
use Illuminate\Http\Response;

class HabitTimeController extends Controller
{
    /**
     * Fetch Daily Notifications (PUBLIC API)
     * @todo obviously better to use oauth if this was a real app
     * @param integer $userId
     * @param HabitInsightService $habitInsightService
     * @return Response
     */
    public function getDailyNotification(int $userId, HabitInsightService $habitInsightService)
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);
        $averageTime = $habitInsightService->generateDailyNotification($userId, $user->timezone ?? 'UTC');

        return response()->json([
            'notification' => $averageTime,
        ], Response::HTTP_OK);
    }

    /**
     * Get Weekly Notifications (PUBLIC API)
     * @todo obviously better to use oauth if this was a real app
     * @param integer $userId
     * @param HabitInsightService $habitInsightService
     * @return Response
     */
    public function getWeeklyNotifications(int $userId, HabitInsightService $habitInsightService)
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);
        $averageTime = $habitInsightService->generateWeeklyNotifications($userId, $user->timezone ?? 'UTC');

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
     * @return Response
     */
    public function startOrEndTimer(int $userId, int $habitTimeId, string $status, HabitService $habitService)
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);
        $response = $habitService->startOrEndTimer($habitTimeId, $userId, $user->timezone ?? 'UTC', $status);
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
     * Tracks the value for a specific habit transaction
     * 
     * @todo obviously better to use oauth if this was a real app
     * @param integer $userId
     * @param integer $habitId
     * @param HabitService $habitService
     * @return Response
     */
    public function store(int $userId, int $habitTimeId, string $value, HabitService $habitService )
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);
        $response = $habitService->saveHabitTransaction($habitTimeId, $userId, $user->timezone ?? 'UTC', $value);

        if ($response) {
            return response()->json([
                'status' => 'success',
                'message' => 'Habit added.',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Habit not added',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Ends all timers for a user (PUBLIC API)
     * @param integer $userId
     * @param HabitInsightRepository $habitInsightRepository
     * @return void
     */
    public function endTimers(int $userId, HabitInsightRepository $habitInsightRepository)
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);
        $habitIds = request()->has('ids') ? explode(',', request()->get('ids')) : [];
        $habitInsightRepository->endAllActiveHabits($userId, $user->timezone ?? 'UTC', $habitIds);

        return response()->json([
            'status' => 'success',
            'message' => 'Habit timers ended successfully',
        ], Response::HTTP_OK);
    }


    /**
     * Check if a habit is active (PUBLIC API)
     * 
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

    public function fetchNewHabitTransactions(HttpRequest $request, HabitInsightRepository  $habitInsightRepository)
    {
        // TODO: Use request form
        $lastUpdatedAt = $request->input('last_updated_at');
        if (!$lastUpdatedAt) {
            return response()->json([
                'status' => 'error',
                'message' => 'last_updated_at is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $items = $habitInsightRepository->fetchNewHabitTransactions($lastUpdatedAt);

        return response()->json([
            'status' => 'success',
            'habit_transactions' => $items,
        ], Response::HTTP_OK);
    }
}
