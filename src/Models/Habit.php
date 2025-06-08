<?php

namespace NickKlein\Habits\Models;

use Illuminate\Database\Eloquent\Model;

class Habit extends Model
{
    protected $primaryKey = 'habit_id';

    public function habit_transactions()
    {
        return $this->hasMany(HabitTime::class);
    }

    public function habit_users()
    {
        return $this->hasOne(HabitUser::class, 'id', 'habit_id');
    }
}
