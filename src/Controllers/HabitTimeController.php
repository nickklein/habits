<?php

namespace NickKlein\Habits\Controllers;

use NickKlein\Habits\Requests\HabitTimeRequests;
use NickKlein\Habits\Requests\HabitTimerRequests;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use NickKlein\Habits\Services\HabitInsightService;
use NickKlein\Habits\Services\HabitService;
use App\Services\LogsService;
use App\Services\PushoverService;
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
    public function transactions(HabitService $service, HabitInsightRepository $insightRepository)
    {
        return Inertia::render('Habits/Transactions', [
            'lists' => $service->getTransactions(),
            'anyHabitActive' => $insightRepository->anyHabitActive(Auth::user()->id),
        ]);
    }

    public function create(HabitService $habitService)
    {
        return Inertia::render('Habits/Add', [
            'habits' => $habitService->getHabits(),
            'times' => [
                'start_date' => date('Y-m-d'),
                'start_time' => date('H:i:s'),
                'end_date' => date('Y-m-d', strtotime('+15 minutes')),
                'end_time' => date('H:i:s', strtotime('+15 minutes')),
            ]
        ]);
    }
    /**
     * Tracks the time of a habit (APP)
     *
     * @param integer $userId
     * @param integer $habitId
     * @param HabitService $habitService
     * @return void
     */
    public function storeHabitTimes(HabitTimeRequests $request, HabitService $habitService)
    {
        $fields = $request->validated();
        $response = $habitService->storeHabitTime(Auth::user()->id, $fields['habit_id'], $fields['start_date'], $fields['start_time'], $fields['end_date'], $fields['end_time']);
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
    public function destroy(int $habitTimeId, HabitService $service)
    {
        $response = $service->deleteHabitTime($habitTimeId, Auth::user()->id);
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
    public function editHabitTimes(int $habitTimesId, HabitService $service, TagsRepository $tagsRepository)
    {
        return Inertia::render('Habits/Edit', [
            'item' => $service->getHabitTime(Auth::user()->id, $habitTimesId),
            'habits' => $service->getHabits(),
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
    public function updateHabitTimes(int $habitTimeId, HabitTimeRequests $request, HabitService $service)
    {
        $fields = $request->validated();
        $response = $service->updateHabitTime($habitTimeId, Auth::user()->id, $fields['habit_id'], $fields['start_date'], $fields['start_time'], $fields['end_date'], $fields['end_time']);
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
    public function timerCreate(HabitService $habitService)
    {
        return Inertia::render('Habits/AddTimer', [
            'habits' => $habitService->getHabits(),
        ]);
    }

    /**
     * Create Habit Timer Store Function
     *
     * @param HabitTimerRequests $request
     * @param HabitInsightService $insightService
     * @param HabitInsightRepository $insightRepository
     */
    public function timerStore(HabitTimerRequests $request, HabitInsightService $insightService)
    {
        $fields = $request->validated();
        $response = $insightService->manageHabitTime($fields['habit_id'], Auth::user()->id, 'on');
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
