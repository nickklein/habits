<?php

namespace NickKlein\Habits\Controllers;

use Carbon\Carbon;
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
            'todaysDate' => Carbon::now(Auth::user()->timezone)->format('Y-m-d'),
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
    public function getHabitUserSummary(int $habitUserId, string $page, Request $request, HabitService $habitService, HabitInsightService $habitInsightService, HabitInsightRepository $habitInsightRepository)
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

        $summary = $habitInsightService->getSingleHabitSummary($habitUser, $page, Auth::user()->timezone, $habitService, $habitInsightRepository, $selectedDate);

        return response()->json($summary);
    }

    /**
     * Show the add transaction page for a habit
     *
     * @param int $habitId
     * @param HabitInsightRepository $habitInsightRepository
     * @return \Inertia\Response
     */
    public function addTransaction(int $habitId, HabitInsightRepository $habitInsightRepository)
    {
        $habitUser = HabitUser::with('habit')
            ->where('habit_id', $habitId)
            ->where('user_id', Auth::user()->id)
            ->first();

        if (!$habitUser) {
            abort(404, 'Habit not found');
        }

        return Inertia::render('Habits/AddTransaction', [
            'habitUser' => [
                'id' => $habitUser->id,
                'habit_id' => $habitUser->habit_id,
                'name' => $habitUser->habit->name,
                'icon' => $habitUser->icon,
                'color_index' => $habitUser->color_index,
                'habit_type' => $habitUser->habit_type,
                'is_active' => $habitInsightRepository->isHabitActive($habitId, Auth::user()->id),
            ],
        ]);
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
