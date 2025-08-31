<?php

namespace NickKlein\Habits\Controllers;

use NickKlein\Habits\Requests\CreateHabitRequest;
use NickKlein\Habits\Services\HabitService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use NickKlein\Habits\Services\HabitInsightService;

class HabitController extends Controller
{
    public function __construct(private HabitService $habitService)
    {
        //
    }

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
            ->orderBy('streak_time_type')
            ->orderBy('streak_goal')
            ->pluck('habit_user.id')
            ->toArray();

        return Inertia::render('Habits/Index', [
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
     * Display the create habit form
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Habits/CreateHabit');
    }

    /**
     * Store a newly created habit in storage
     *
     * @param CreateHabitRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CreateHabitRequest $request)
    {
        $fields = $request->validated();
        $response = $this->habitService->createHabit(Auth::user()->id, $fields);
        
        if ($response) {
            return redirect()->route('habits.index')->with([
                'message' => 'Habit created successfully',
            ], Response::HTTP_OK);
        }

        return back()->with([
            'message' => 'Habit not created',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
