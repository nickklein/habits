<?php

namespace NickKlein\Habits\Repositories;

use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitTimesTag;
use NickKlein\Tags\Models\Tags;
use NickKlein\Tags\Models\UserTags;
use Illuminate\Database\Eloquent\Collection;
use NickKlein\Tags\Repositories\TagsRepository as BaseRepository;

class TagsRepository extends BaseRepository 
{
    /**
     * create tags for habits
     *
     * @return collection
     */
    public function createHabitTimeTag(int $userId, int $habitTimeId, string $tagName)
    {
        // Find Tag through repo
        $tags = Tags::firstOrCreate([
            'tag_name' => $tagName
        ]);

        // Bail if habit time doesn't belon to user
        if (!$this->isOwnedByUser($habitTimeId, $userId)) {
            return new Collection([]);
        }

        return HabitTimesTag::create([
            'habit_time_id' => $habitTimeId,
            'tag_id' => $tags->tag_id,
        ]);
    }

    /**
     * Check if habit times is owned by an user
     * @todo middleware?policy?
     *
     * @param integer $habitTimeId
     * @param integer $userId
     * @return boolean
     */
    public function isOwnedByUser(int $habitTimeId, int $userId): bool
    {
        return HabitTime::where('id', $habitTimeId)->where('user_id', $userId)->exists();
    }

    /**
     * Find Habit Times Tag
     *
     * @param integer $habitTimeId
     * @param integer $tagId
     * @return HabitTimesTag
     */
    public function findHabitTimesTag(int $habitTimeId, int $tagId, int $userId): HabitTimesTag
    {
        return HabitTimesTag::where('habit_time_id', $habitTimeId)
            ->join('habit_transactions', 'habit_transactions.id', '=', 'habit_transactions_tags.id')
            ->where('habit_transactions.user_id', $userId)
            ->where('tag_id', $tagId)
            ->first();
    }

    /**
     * Get list of personalized tags
     *
     * @return collection
     */
    public function listHabitTimesTags(int $habitTimeId, int $userId)
    {
        return Tags::whereHas('habitTimes', function ($query) use ($habitTimeId, $userId) {
            $query->where('habit_transactions.id', $habitTimeId)
                ->where('habit_transactions.user_id', $userId);
        })->pluck('tag_name');
    }
}
