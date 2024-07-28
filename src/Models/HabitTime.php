<?php

namespace NickKlein\Habits\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tag;

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
        return $this->belongsToMany(Tag::class, 'habit_times_tags', 'habit_time_id', 'tag_id');
    }
}
