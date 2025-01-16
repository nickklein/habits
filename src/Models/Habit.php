<?php

namespace NickKlein\Habits\Models;

use Illuminate\Database\Eloquent\Model;

class Habit extends Model
{
    public $timestamps = false;

    public function habit_times()
    {
        return $this->hasMany(HabitTime::class);
    }

    public function habit_users()
    {
        return $this->hasOne(HabitUser::class, 'id', 'habit_id');
    }
}
