<?php

namespace NickKlein\Habits\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NickKlein\Tags\Models\Tags;

class HabitTimesTag extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'habit_times_tags';

    protected $fillable = [
        'habit_time_id',
        'tag_id',
    ];

    public function habitTime()
    {
        return $this->belongsTo(HabitTime::class, 'habit_time_id');
    }

    public function tag()
    {
        return $this->belongsTo(Tags::class, 'tag_id');
    }
}
