<?php

namespace NickKlein\Habits\Models;

use NickKlein\Habits\Models\HabitTime;
use NickKlein\Tags\Models\Tags as GlobalTags;

class Tags extends GlobalTags 
{
    public function habitTimes()
    {
        return $this->belongsToMany(HabitTime::class, 'habit_times_tags', 'tag_id', 'habit_time_id');
    }
}
