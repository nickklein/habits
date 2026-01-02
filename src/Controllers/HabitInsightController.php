<?php

namespace NickKlein\Habits\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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

        /* dd($habitInsightService->getYearlySummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository)); */
        return Inertia::render('Habits/Insights/ShowInsights', [
            'habit' => $habitsUser,
            'color' => $habitsUser->color_index ?? '#ffffff',
            'streaks' => $habitInsightService->getStreaks($habitsUser, Auth::user()->id, Auth::user()->timezone, $habitId, $habitInsightRepository),
            /* 'monthlyCharts' => $habitInsightService->monthlyCharts($habitsUser, Auth::user()->id, Auth::user()->timezone, $habitId, $habitInsightRepository), */
            'dailySummaryHighlights' => $habitInsightService->getDailySummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'weeklyAveragesHighlights' => $habitInsightService->getWeeklyAverageHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'weeklySummaryHighlights' => $habitInsightService->weeklySummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'monthlyAveragesHighlights' => $habitInsightService->getMonthlyAverageHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'monthlySummaryHighlights' => $habitInsightService->getMonthlySummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'yearlySummaryHighlights' => $habitInsightService->getYearlySummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
            'totalSummaryHighlights' => $habitInsightService->getTotalSummaryHighlights($habitsUser, Auth::user()->timezone, $habitService, $habitInsightRepository),
        ]);
    }

    public function getChartInformation(Request $request, HabitService $habitService, HabitInsightService $habitInsightService, HabitInsightRepository $habitInsightRepository): JsonResponse
    {
        // TODO: Make the route habitId restricted to integer only inside the routes (aka where())
        $habitId = (int)$request->route('habitId');
        $habitsUser = $habitService->getHabit($habitId, Auth::user()->id);

        if (!$habitsUser) {
            abort(503);
        }

        $response = $habitInsightService->charts($habitsUser, Auth::user()->id, Auth::user()->timezone, $habitId, $habitInsightRepository);

        return response()->json($response);
    }

    public function getYearlyComparisonChartForHabit(int $habitId, HabitInsightService $habitInsightService, HabitInsightRepository $habitInsightRepository): JsonResponse
    {
        $response = $habitInsightService->yearlyComparisonChartForHabit($habitId, Auth::user()->id, Auth::user()->timezone, $habitInsightRepository);

        return response()->json($response);
    }
}
