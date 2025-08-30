<?php

namespace NickKlein\Habits\Controllers;

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
     * TODO: Should return all the habit_user.ids
     *
     * @param HabitInsightService $habitInsightService
     * @return Inertia
     */
    public function index(HabitInsightService $insightService, HabitService $service, HabitInsightRepository $insightRepository)
    {
        $habitUser = HabitUser::with(['habit', 'children'])
            ->join('habits', 'habit_user.habit_id', '=', 'habits.habit_id')
            ->whereNull('parent_id')
            ->where('user_id', Auth::user()->id)
            ->where('archive', false)
            ->orderBy('habits.name')
            ->get();

        $habits = $insightService->getDailySummaryForHabits(Auth::user()->id, Auth::user()->timezone, $habitUser, $service, $insightRepository);

        return Inertia::render('Habits/Index', [
            'habits' => $habits,
        ]);
    }

    // TODO: Add Forms Request
    public function getDailySummaries(Request $request)
    {
        // $request->habit_user_id, which is habit_user.id
        // Returns getDailySummaryForHabitUserId

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
