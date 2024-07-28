<?php

namespace Tests\Unit;

use App\Http\Controllers\HabitController;
use App\Models\User;
use App\Models\Habit;
use App\Models\HabitTime;
use App\Services\HabitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\JsonResponse;

class HabitControllerTest extends TestCase
{
    use RefreshDatabase;
}
