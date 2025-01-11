<?php

namespace NickKlein\Habits\Tests;

use App\Http\Controllers\HabitController;
use NickKlein\Habits\Models\User;
use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitTime;
use App\Services\HabitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use NickKlein\Habits\Tests\TestCase;

class HabitControllerTest extends TestCase
{
    use RefreshDatabase;
}
