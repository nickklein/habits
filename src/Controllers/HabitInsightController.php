<?php

namespace NickKlein\Habits\Controllers;

use App\Http\Controllers\Controller;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use NickKlein\Habits\Services\HabitInsightService;
use NickKlein\Habits\Services\HabitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HabitInsightController extends Controller
{
    /**
     * Habit homepage
     *
     * @param HabitInsightService $habitInsightService
     * @return Inertia
     */
    public function index()
    {
        $habitUserIds = HabitUser::join('habits', 'habit_user.habit_id', '=', 'habits.habit_id')
            ->whereNull('parent_id')
            ->where('user_id', Auth::user()->id)
            ->where('archive', false)
            ->orderBy('habits.name')
            ->pluck('habit_user.id')
            ->toArray();

        return Inertia::render('Habits/Insights/Index', [
            'habitUserIds' => $habitUserIds,
        ]);
    }

    /**
     * Get individual habit user summary for AJAX calls
     *
     * @param integer $habitUserId
     * @param HabitService $habitService
     * @param HabitInsightService $habitInsightService
     * @param HabitInsightRepository $habitInsightRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHabitUserSummary(int $habitUserId, Request $request, HabitService $habitService, HabitInsightService $habitInsightService, HabitInsightRepository $habitInsightRepository)
    {
        $habitUser = HabitUser::with(['habit', 'children'])
            ->where('id', $habitUserId)
            ->where('user_id', Auth::user()->id)
            ->first();

        if (!$habitUser) {
            return response()->json(['error' => 'Habit not found'], 404);
        }

        // Get date from query parameter, default to today
        $selectedDate = $request->query('date', now(Auth::user()->timezone)->toDateString());

        $summary = $habitInsightService->getSingleHabitSummary($habitUser, Auth::user()->timezone, $habitService, $habitInsightRepository, $selectedDate);

        return response()->json($summary);
    }

    /**
     * Show habit insights
     *
     * @param integer $habitId
     * @param HabitService $habitService
     * @param HabitInsightService $habitInsightService
     * @param HabitInsightRepository $habitInsightRepository
     * @return Inertia
     */
    public function show(int $habitId, HabitService $habitService, HabitInsightService $habitInsightService, HabitInsightRepository $habitInsightRepository)
    {
        $habitsUser = $habitService->getHabit($habitId, Auth::user()->id);

        return Inertia::render('Habits/Insights/ShowInsights', [
            'habit' => $habitsUser,
            'color' => $habitsUser->color_index ?? '#ffffff',
            'streaks' => $habitInsightService->getStreaks($habitsUser, Auth::user()->id, Auth::user()->timezone, $habitId, $habitInsightRepository),
            'dailySummaryHighlights' => $habitInsightService->getDailySummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'weeklyAveragesHighlights' => $habitInsightService->getWeeklyAverageHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'weeklySummaryHighlights' => $habitInsightService->weeklySummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'monthlyAveragesHighlights' => $habitInsightService->getMonthlyAverageHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'monthlySummaryHighlights' => $habitInsightService->getMonthlySummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'yearlySummaryHighlights' => $habitInsightService->getYearlySummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository)
        ]);
    }
}
