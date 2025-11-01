<?php

namespace NickKlein\Habits\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HabitUser extends Model
{
    use HasFactory;

    protected $table = 'habit_user';
    public $timestamps = false;

    public function habit()
    {
        return $this->belongsTo(Habit::class, 'id', 'habit_id');
    }

    public function parent()
    {
        return $this->belongsTo(HabitUser::class, 'parent_id')->with('habit');
    }

    public function children()
    {
        return $this->hasMany(HabitUser::class, 'parent_id')
            ->where('archive', false)
            ->with('habit');
    }
}
