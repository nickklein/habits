<?php

namespace NickKlein\Habits\Controllers;

use NickKlein\Habits\Requests\HabitTimeRequests;
use NickKlein\Habits\Requests\HabitTimerRequests;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use NickKlein\Habits\Services\HabitInsightService;
use NickKlein\Habits\Services\HabitService;
use App\Services\LogsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Routing\Controller;
use NickKlein\Habits\Repositories\TagsRepository;
use NickKlein\Habits\Services\TagsService;
use NickKlein\Tags\Requests\TagRequest;

class HabitTimeController extends Controller
{
    public function __construct(private HabitService $habitService, private HabitInsightRepository $habitInsightRepository)
    {
        //
    }
    public function transactions()
    {
        return Inertia::render('Habits/Transactions', [
            'lists' => $this->habitService->getTransactions(Auth::user()->id, Auth::user()->timezone),
            'anyHabitActive' => $this->habitInsightRepository->anyHabitActive(Auth::user()->id),
        ]);
    }

    public function create()
    {
        $timezone = Auth::user()?->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);
        $later = $now->copy()->addMinutes(15);

        return Inertia::render('Habits/Add', [
            'habits' => $this->habitService->getUserHabits(Auth::user()->id),
            'times' => [
                'start_date' => $now->format('Y-m-d'),
                'start_time' => $now->format('H:i:s'),
                'end_date' => $later->format('Y-m-d'),
                'end_time' => $later->format('H:i:s'),
            ]
        ]);
    }
    /**
     * Tracks the time of a habit (APP)
     *
     * @param integer $userId
     * @param integer $habitId
     * @return void
     */
    public function storeHabitTransaction(HabitTimeRequests $request)
    {
        $fields = $request->validated();
        $fields['value'] = $fields['value'] ?? 0;
        $response = $this->habitService->storeHabitTransaction(Auth::user()->id, Auth::user()->timezone, $fields['habit_id'], $fields['value'], $fields['start_date'], $fields['start_time'], $fields['end_date'], $fields['end_time']);
        if ($response) {
            return back()->with([
                'message' => 'Habit time added successfully',
            ], Response::HTTP_OK);
        }

        return back()->with([
            'message' => 'Habit time not added',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Create destroy function that checks if user has permission to delete habit time
     *
     * @param integer $habitTimeId
     * @param HabitService $service
     * @return JSONResponse
     */
    public function destroy(int $habitTimeId)
    {
        $response = $this->habitService->deleteHabitTime($habitTimeId, Auth::user()->id);
        if ($response) {
            return response()->json([
                'message' => 'Habit deleted successfully',
            ]);
        }

        return response()->json([
            'message' => 'Habit not deleted',
        ]);
    }

    /**
     * Edit Habit Times page
     *
     * @param integer $habitTimesId
     * @param HabitService $service
     * @return Inertia
     */
    public function editHabitTransaction(int $habitTimesId, TagsRepository $tagsRepository)
    {
        return Inertia::render('Habits/Edit', [
            'item' => $this->habitService->getHabitTime(Auth::user()->id, Auth::user()->timezone, $habitTimesId),
            'habits' => $this->habitService->getUserHabits(Auth::user()->id),
            'tags' => $tagsRepository->listHabitTimesTags($habitTimesId, Auth::user()->id),
            'tagsAddUrl' => route('habits.transactions.edit.add-tag', ['habitTimesId' => $habitTimesId]),
            'tagsRemoveUrl' => route('habits.transactions.edit.remove-tag', ['habitTimesId' => $habitTimesId]),
        ]);
    }

    /**
     * Update Habit Times
     *
     * @param integer $habitTimeId
     * @param HabitTimeRequests $request
     * @param HabitService $service
     */
    public function updateHabitTransaction(int $habitTimeId, HabitTimeRequests $request)
    {
        $fields = $request->validated();
        $fields['value'] = $fields['value'] ?? 0;
        $response = $this->habitService->updateHabitTransaction($habitTimeId, Auth::user()->id, Auth::user()->timezone, $fields['habit_id'], $fields['value'], $fields['start_date'], $fields['start_time'], $fields['end_date'], $fields['end_time']);
        if ($response) {
            return back()->with(['message' => __('Habit updated successfully')], 200);
        }

        return back()->with(['message' => __('Habit not updated')], 403);
    }

    /**
     * Add Timer page
     *
     * @param HabitService $habitService
     * @return Inertia
     */
    public function timerCreate()
    {
        return Inertia::render('Habits/AddTimer', [
            'habits' => $this->habitService->getUserHabits(Auth::user()->id, 1),
        ]);
    }

    /**
     * Create Habit Timer Store Function
     *
     * @param HabitTimerRequests $request
     */
    public function timerStore(HabitTimerRequests $request)
    {
        $fields = $request->validated();
        $response = $this->habitService->manageHabitTransaction($fields['habit_id'], Auth::user()->id, Auth::user()->timezone, 'on');
        if ($response) {
            return redirect()->route('habits.transactions')->with([
                'message' => 'Habit time added successfully',
            ], Response::HTTP_OK);
        }

        return redirect()->route('habits.transactions')->with([
            'message' => 'Habit time not added',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * End Habit Timer
     *
     * @param HabitInsightRepository $insightRepository
     * @return void
     */
    public function timerEnd(HabitInsightRepository $insightRepository)
    {
        $insightRepository->endAllActiveHabits(Auth::user()->id);

        return redirect()->route('habits.transactions')->with([
            'message' => 'Habit time ended successfully',
        ], Response::HTTP_OK);
    }


    /**
     * Get Tags for habit times
     *
     * @param integer $habitTimeId
     * @param TagsRepository $tagsRepository
     * @return void
     */
    public function getTags(int $habitTimeId, TagsRepository $tagsRepository)
    {
        $response = $tagsRepository->listHabitTimesTags(Auth::user()->id, $habitTimeId);

        return response()->json($response);
    }

    /**
     * Add Tag for habit times
     *
     * @param TagRequest $request
     * @param TagsRepository $tagsRepository
     * @return JsonResponse
     */
    public function addTag(TagRequest $request, TagsRepository $tagsRepository): JsonResponse
    {
        $fields = $request->validated();

        $response = $tagsRepository->createHabitTimeTag(Auth::user()->id, $request->route('habitTimesId'), $fields['tagName']);

        return response()->json($response);
    }

    /**
     * Destroy Tag for habit times
     *
     * @param TagRequest $request
     * @param TagsService $service
     * @return JsonResponse
     */
    public function removeTag(TagRequest $request, TagsService $tagsService, LogsService $logService)
    {
        try {
            $fields = $request->validated();

            $response = $tagsService->destroyHabitTimesTag(Auth::user()->id, $request->route('habitTimesId'), $fields['tagName'], $logService);
        } catch (Exception $e) {
            // Log the exception using the LogsService

            $logService->handle("Error", "Exception occurred in removeTag: " . $e->getMessage());
            return response()->json(['action' => 'error', 'message' => 'An error occurred while processing the request.']);
        }

        return response()->json($response);
    }
}
