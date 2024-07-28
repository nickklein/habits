<?php

namespace Tests\Unit\Habits;

use App\Models\HabitTime;
use App\Models\User;
use App\Repositories\HabitInsightRepository;
use App\Services\HabitInsightService;
use App\Services\HabitService;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HabitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_habit_time_starts_habit_if_not_active()
    {
        $user = User::factory()->create();
        $habitId = 1; // adjust this as necessary
        $service = new HabitInsightService();
        $repository = new HabitInsightRepository();

        $this->assertDatabaseMissing('habit_times', [
            'habit_id' => $habitId,
            'user_id' => $user->id,
            'end_time' => null
        ]);

        $service->manageHabitTime($habitId, $user->id, 'on', $repository);

        $this->assertDatabaseHas('habit_times', [
            'habit_id' => $habitId,
            'user_id' => $user->id,
            'end_time' => null
        ]);
    }

    public function test_manage_habit_time_ends_habit_if_active()
    {
        $user = User::factory()->create();
        $habitId = 1; // adjust this as necessary

        $service = new HabitInsightService();
        $repository = new HabitInsightRepository();

        // Start the habit
        $service->manageHabitTime($habitId, $user->id, 'on', $repository);

        $this->assertDatabaseHas('habit_times', [
            'habit_id' => $habitId,
            'user_id' => $user->id,
            'end_time' => null
        ]);

        // End the habit
        $service->manageHabitTime($habitId, $user->id, 'off', $repository);

        $this->assertDatabaseMissing('habit_times', [
            'habit_id' => $habitId,
            'user_id' => $user->id,
            'end_time' => null
        ]);

        $this->assertDatabaseHas('habit_times', [
            'habit_id' => $habitId,
            'user_id' => $user->id
        ]);
    }

    public function testUpdateHabitTime()
    {
        // Arrange: Create a user and a habitTime
        $user = User::factory()->create();
        $habitTime = HabitTime::factory()->create(['user_id' => $user->id]);

        // Create a new set of data to update the habitTime with
        $newHabitId = 2;

        $newStartDate = date('Y-m-d');  // Get the current date and time
        $newStartTime = date('H:i:s');  // Get the current date and time
        $newEndDate = date('Y-m-d', strtotime('+1 hour', strtotime($newStartTime)));
        $newEndTime = date('H:i:s', strtotime('+1 hour', strtotime($newStartTime)));


        // Create an instance of your service
        $service = new HabitService();

        // Act: Call the method you're testing
        $result = $service->updateHabitTime($habitTime->id, $user->id, $newHabitId, $newStartDate, $newStartTime, $newEndDate, $newEndTime);

        // Assert: Check that the function returned true (indicating a successful update)
        $this->assertTrue($result);

        // Refresh the instance of habitTime from the database
        $habitTime->refresh();

        // Check that the data was updated correctly
        $this->assertEquals($newHabitId, $habitTime->habit_id);
        $this->assertEquals($newStartDate . ' ' . $newStartTime, $habitTime->start_time);
        $this->assertEquals($newEndDate . ' ' . $newEndTime, $habitTime->end_time);
        $this->assertEquals(Carbon::parse($newStartTime)->diffInSeconds($newEndTime), $habitTime->duration);
    }

    public function testConvertSecondsToMinutesOrHours()
    {
        $minutesTest = 3000;
        $hoursTest = 3600 * 2;

        // Create an instance of your service
        $service = new HabitService();

        // Act: Call the method you're testing
        $minutesResponse = $service->convertSecondsToMinutesOrHoursV2($minutesTest);
        $hoursResponse = $service->convertSecondsToMinutesOrHoursV2($hoursTest);

        // Assert: Check that the function returned the correct value
        $this->assertEquals(['value' => 50, 'unit' => 'min', 'unit_full' => 'minutes'], $minutesResponse);
        $this->assertEquals(['value' => 2, 'unit' => 'hrs', 'unit_full' => 'hours'], $hoursResponse);
    }
}
