<?php

namespace NickKlein\Habits\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NickKlein\Habits\Models\HabitTime;

class HabitEndedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public int $userId, public $timezone = 'UTC', public HabitTime $habitTime)
    {
        //
    }
}
