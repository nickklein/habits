<?php

namespace NickKlein\Habits\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NickKlein\Tags\Models\Tags;

class HabitTime extends Model
{
    use HasFactory;
    public $timestamps = false;

    //@todo remove ids from fillables
    protected $fillable = [
        'user_id',
        'habit_id',
        'start_time',
        'end_time',
        'duration',
    ];

    public function habits()
    {
        return $this->belongsToMany(Tags::class, 'habit_times_tags', 'habit_time_id', 'tag_id');
    }
}
