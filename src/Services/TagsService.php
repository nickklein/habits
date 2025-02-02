<?php

namespace NickKlein\Habits\Services;

use App\Services\LogsService;
use NickKlein\Habits\Repositories\TagsRepository;
use Exception;
use NickKlein\Tags\Services\TagsService as BaseService;

class TagsService extends BaseService 
{
    private $repository;

    public function __construct(TagsRepository $repository)
    {
        $this->repository = $repository;
    }

    // destroy tag for habit times
    public function destroyHabitTimesTag(int $userId, int $habitTimeId, string $tagName, LogsService $log): array
    {
        try {
            $tag = $this->repository->findTag($tagName);
            if (empty($tag)) {
                return [];
            }

            $habitTimesTag = $this->repository->findHabitTimesTag($habitTimeId, $tag->tag_id, $userId);
            if (!$habitTimesTag->delete()) {
                return [];
            }

            return ['action' => 'success'];
        } catch (Exception $e) {
            // Log the exception using the LogsService
            $log->handle("Error", "Exception occurred in destroyHabitTimesTag: " . $e->getMessage());
            return ['action' => 'error', 'message' => 'An error occurred while processing the request.'];
        }
    }
}
