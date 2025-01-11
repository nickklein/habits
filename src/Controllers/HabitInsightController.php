<?php

namespace NickKlein\Habits\Controllers;

use Illuminate\Http\Request;
use NickKlein\Habits\Models\Habit;
use App\Http\Controllers\Controller;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use NickKlein\Habits\Services\HabitInsightService;
use NickKlein\Habits\Services\HabitService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HabitInsightController extends Controller
{
    /**
     * Habit homepage
     *
     * @param HabitInsightService $habitInsightService
     * @return Inertia
     */
    public function index(HabitInsightService $insightService, HabitService $service, HabitInsightRepository $insightRepository)
    {
        $habitUser = HabitUser::with(['habit', 'children'])
            ->whereNull('parent_id')
            ->where('user_id', Auth::user()->id)
            ->where('archive', false)
            ->get();

        $habits = $insightService->getDailySummaryForHabits(Auth::user()->id, $habitUser, $service, $insightRepository);

        return Inertia::render('Habits/Index', [
            'habits' => $habits,
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

        return Inertia::render('Habits/ShowInsights', [
            'habit' => $habitsUser,
            'color' => HabitInsightService::HABIT_COLOR_INDEX[$habitsUser->parent->habit_id ?? $habitsUser->habit_id],
            'streaks' => $habitInsightService->getStreaks($habitsUser, Auth::user()->id, $habitId, $habitInsightRepository),
            'dailySummaryHighlights' => $habitInsightService->getDailySummaryHighlights($habitsUser, $habitService, $habitInsightRepository),
            'weeklyAveragesHighlights' => $habitInsightService->getWeeklyAverageHighlights($habitsUser, $habitService, $habitInsightRepository),
            'weeklySummaryHighlights' => $habitInsightService->weeklySummaryHighlights($habitsUser, $habitService, $habitInsightRepository),
            'monthlyAveragesHighlights' => $habitInsightService->getMonthlyAverageHighlights($habitsUser, $habitService, $habitInsightRepository),
            'monthlySummaryHighlights' => $habitInsightService->getMonthlySummaryHighlights($habitsUser, $habitService, $habitInsightRepository),
            'yearlySummaryHighlights' => $habitInsightService->getYearlySummaryHighlights($habitsUser, $habitService, $habitInsightRepository)
        ]);
    }
}
