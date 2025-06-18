<?php

namespace NickKlein\Habits\Tests;

use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Services\HabitService;
use NickKlein\Habits\Tests\TestCase;
use Carbon\Carbon;

class HabitServiceValueTest extends TestCase
{
    protected $habitService;

    public function setUp(): void
    {
        parent::setUp();
        $this->habitService = $this->app->make(HabitService::class);
    }

    /** @test */
    public function it_can_start_a_timer_for_time_habit()
    {
        // Arrange
        $userId = 1;
        $habitId = 1;
        
        // Create a time-based habit user
        $habitUser = HabitUser::where('user_id', $userId)->where('habit_id', $habitId)->first();
        $habitUser->habit_type = 'time';
        $habitUser->save();

        // Act
        $result = $this->habitService->startOrEndTimer($habitId, $userId, 'UTC', 'on');

        // Assert
        $this->assertTrue($result);
        
        // Check that a habit time record was created
        $habitTime = HabitTime::where('user_id', $userId)
            ->where('habit_id', $habitId)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first();
            
        $this->assertNotNull($habitTime);
        $this->assertEquals($habitId, $habitTime->habit_id);
        $this->assertEquals($userId, $habitTime->user_id);
        $this->assertNull($habitTime->end_time);
    }

    /** @test */
    public function it_can_end_a_timer_for_time_habit()
    {
        // Arrange
        $userId = 1;
        $habitId = 1;
        
        // Create a time-based habit user
        $habitUser = HabitUser::where('user_id', $userId)->where('habit_id', $habitId)->first();
        $habitUser->habit_type = 'time';
        $habitUser->save();

        // Create an active timer
        $habitTime = HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $habitId,
            'start_time' => Carbon::now()->subMinutes(30),
            'end_time' => null,
        ]);

        // Act
        $result = $this->habitService->startOrEndTimer($habitId, $userId, 'UTC', 'off');

        // Assert
        $this->assertTrue($result);
        
        // Check that the habit time record was ended
        $habitTime->refresh();
        $this->assertNotNull($habitTime->end_time);
        $this->assertNotNull($habitTime->duration);
        $this->assertGreaterThan(1500, $habitTime->duration); // Should be around 1800 seconds (30 minutes)
    }

    /** @test */
    public function it_returns_false_when_ending_timer_with_no_active_timer()
    {
        // Arrange
        $userId = 1;
        $habitId = 1;
        
        // Create a time-based habit user
        $habitUser = HabitUser::where('user_id', $userId)->where('habit_id', $habitId)->first();
        $habitUser->habit_type = 'time';
        $habitUser->save();

        // Ensure no active timers exist
        HabitTime::where('user_id', $userId)->where('habit_id', $habitId)->delete();

        // Act
        $result = $this->habitService->startOrEndTimer($habitId, $userId, 'UTC', 'off');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_when_habit_user_not_found_for_start_or_end_timer()
    {
        // Arrange
        $userId = 999; // Non-existent user
        $habitId = 999; // Non-existent habit

        // Act
        $result = $this->habitService->startOrEndTimer($habitId, $userId, 'UTC', 'on');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_save_habit_transaction_for_unit_habit()
    {
        // Arrange
        $userId = 1;
        $habitId = 1;
        $value = '5'; // 5 units
        
        // Create a unit-based habit user
        $habitUser = HabitUser::where('user_id', $userId)->where('habit_id', $habitId)->first();
        $habitUser->habit_type = 'unit';
        $habitUser->save();

        // Act
        $result = $this->habitService->saveHabitTransaction($habitId, $userId, 'UTC', $value);

        // Assert
        $this->assertTrue($result);
        
        // Check that a habit time record was created
        $habitTime = HabitTime::where('user_id', $userId)
            ->where('habit_id', $habitId)
            ->where('duration', 5)
            ->first();
            
        $this->assertNotNull($habitTime);
        $this->assertEquals(5, $habitTime->duration);
    }

    /** @test */
    public function it_can_save_habit_transaction_for_ml_habit()
    {
        // Arrange
        $userId = 1;
        $habitId = 1;
        $value = '250'; // 250ml
        
        // Create a ml-based habit user
        $habitUser = HabitUser::where('user_id', $userId)->where('habit_id', $habitId)->first();
        $habitUser->habit_type = 'ml';
        $habitUser->save();

        // Act
        $result = $this->habitService->saveHabitTransaction($habitId, $userId, 'UTC', $value);

        // Assert
        $this->assertTrue($result);
        
        // Check that a habit time record was created
        $habitTime = HabitTime::where('user_id', $userId)
            ->where('habit_id', $habitId)
            ->where('duration', 250)
            ->first();
            
        $this->assertNotNull($habitTime);
        $this->assertEquals(250, $habitTime->duration);
    }

    /** @test */
    public function it_returns_false_when_habit_user_not_found_for_save_transaction()
    {
        // Arrange
        $userId = 999; // Non-existent user
        $habitId = 999; // Non-existent habit
        $value = '5';

        // Act
        $result = $this->habitService->saveHabitTransaction($habitId, $userId, 'UTC', $value);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_update_habit_transaction()
    {
        // Arrange
        $userId = 1;
        $habitId = 1;
        
        // Create a time-based habit user
        $habitUser = HabitUser::where('user_id', $userId)->where('habit_id', $habitId)->first();
        $habitUser->habit_type = 'time';
        $habitUser->save();

        // Create an existing habit time record
        $habitTime = HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $habitId,
            'start_time' => Carbon::yesterday()->setTime(10, 0),
            'end_time' => Carbon::yesterday()->setTime(11, 0),
            'duration' => 3600,
        ]);

        $fields = [
            'habit_id' => $habitId,
            'start_date' => Carbon::yesterday()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_date' => Carbon::yesterday()->format('Y-m-d'),
            'end_time' => '10:30:00',
        ];

        // Act
        $result = $this->habitService->updateHabitTransaction($habitTime->id, $userId, 'UTC', $fields);

        // Assert
        $this->assertTrue($result);
        
        // Check that the record was updated
        $habitTime->refresh();
        $this->assertEquals(5400, $habitTime->duration); // 1.5 hours = 5400 seconds
    }

    /** @test */
    public function it_returns_false_when_updating_non_existent_habit_transaction()
    {
        // Arrange
        $userId = 1;
        $habitId = 1;
        $habitTimeId = 999; // Non-existent habit time
        
        // Create a time-based habit user
        $habitUser = HabitUser::where('user_id', $userId)->where('habit_id', $habitId)->first();
        $habitUser->habit_type = 'time';
        $habitUser->save();

        $fields = [
            'habit_id' => $habitId,
            'start_date' => Carbon::yesterday()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_date' => Carbon::yesterday()->format('Y-m-d'),
            'end_time' => '10:30:00',
        ];

        // Act
        $result = $this->habitService->updateHabitTransaction($habitTimeId, $userId, 'UTC', $fields);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_when_updating_habit_transaction_with_no_habit_user()
    {
        // Arrange
        $userId = 999; // Non-existent user
        $habitId = 999; // Non-existent habit
        $habitTimeId = 1;

        $fields = [
            'habit_id' => $habitId,
            'start_date' => Carbon::yesterday()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_date' => Carbon::yesterday()->format('Y-m-d'),
            'end_time' => '10:30:00',
        ];

        // Act
        $result = $this->habitService->updateHabitTransaction($habitTimeId, $userId, 'UTC', $fields);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_store_habit_transaction_with_specific_time_range()
    {
        // Arrange
        $userId = 1;
        $habitId = 1;
        
        // Create a time-based habit user
        $habitUser = HabitUser::where('user_id', $userId)->where('habit_id', $habitId)->first();
        $habitUser->habit_type = 'time';
        $habitUser->save();

        $fields = [
            'habit_id' => $habitId,
            'start_date' => Carbon::yesterday()->format('Y-m-d'),
            'start_time' => '14:00:00',
            'end_date' => Carbon::yesterday()->format('Y-m-d'),
            'end_time' => '15:30:00',
        ];

        // Act
        $result = $this->habitService->storeHabitTransaction($userId, 'UTC', $fields);

        // Assert
        $this->assertTrue($result);
        
        // Check that a new habit time record was created
        $habitTime = HabitTime::where('user_id', $userId)
            ->where('habit_id', $habitId)
            ->where('duration', 5400) // 1.5 hours = 5400 seconds
            ->first();
            
        $this->assertNotNull($habitTime);
        $this->assertEquals(5400, $habitTime->duration);
    }

    /** @test */
    public function it_returns_false_when_storing_habit_transaction_with_no_habit_user()
    {
        // Arrange
        $userId = 999; // Non-existent user
        $habitId = 999; // Non-existent habit

        $fields = [
            'habit_id' => $habitId,
            'start_date' => Carbon::yesterday()->format('Y-m-d'),
            'start_time' => '14:00:00',
            'end_date' => Carbon::yesterday()->format('Y-m-d'),
            'end_time' => '15:30:00',
        ];

        // Act
        $result = $this->habitService->storeHabitTransaction($userId, 'UTC', $fields);

        // Assert
        $this->assertFalse($result);
    }
}