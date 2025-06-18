<?php

namespace NickKlein\Habits\Tests;

use NickKlein\Habits\Controllers\Public\HabitTimeController;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Tests\TestModels\User;
use NickKlein\Habits\Services\HabitInsightService;
use NickKlein\Habits\Tests\TestCase;
use NickKlein\Habits\Services\HabitService;
use Mockery;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class HabitPublicRoutesTest extends TestCase
{
    public function testGetDailyNotification()
    {
        // Arrange: Create a user
        $user = User::find(1);

        // Arrange: Mock HabitService
        $mockService = Mockery::mock(HabitInsightService::class);
        $mockService->shouldReceive('generateDailyNotification')
            ->once()
            ->with($user->id, $user->timezone ?? 'UTC')
            ->andReturn('Habit1: 10, Habit2: 20, ');

        // Act: Call the getDailyNotification method on the controller
        $controller = $this->app->make(HabitTimeController::class);
        $response = $controller->getDailyNotification($user->id, $mockService);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->status());
        $this->assertEquals('Habit1: 10, Habit2: 20, ', $response->getData()->notification);
    }

    public function testGetWeeklyNotifications()
    {
        // Arrange: Create a user
        $user = User::find(1);

        // Arrange: Mock HabitService
        $mockService = Mockery::mock(HabitInsightService::class);
        $mockService->shouldReceive('generateWeeklyNotifications')
            ->once()
            ->with($user->id, $user->timezone ?? 'UTC')
            ->andReturn('Habit1: 10 (5%), Habit2: 20 (10%), ');

        // App::instance method binds the mock into the service container
        // So when Laravel tries to resolve HabitService class, it gets the mock instance instead
        $this->app->instance(HabitInsightService::class, $mockService);

        // Act: Call the getWeeklyNotifications method on the controller
        $controller = $this->app->make(HabitTimeController::class);
        $response = $controller->getWeeklyNotifications($user->id, $mockService);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->status());
        $this->assertEquals('Habit1: 10 (5%), Habit2: 20 (10%), ', $response->getData()->notification);
    }

    public function testStoreHabitTime()
    {
        // Arrange: Create a user
        $user = User::find(1);

        // Create a habit time record
        $habitTime = HabitTime::create([
            'user_id' => $user->id,
            'habit_id' => 1,
            'start_time' => now(),
            'end_time' => null,
        ]);

        // Arrange: Mock HabitService
        $mockService = Mockery::mock(HabitService::class);
        $mockService->shouldReceive('saveHabitTransaction')
            ->once()
            ->with($habitTime->id, $user->id, $user->timezone ?? 'UTC', 'on')
            ->andReturn(true);

        // App::instance method binds the mock into the service container
        $this->app->instance(HabitService::class, $mockService);

        // Act: Call the store method on the controller
        $controller = new HabitTimeController;
        $response = $controller->store($user->id, $habitTime->id, 'on', $mockService);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->status());
        $this->assertEquals('Habit added.', $response->getData()->message);
    }

    public function testStoreHabitTimeFail()
    {
        // Arrange: Create a user
        $user = User::find(1);

        // Create a habit time record
        $habitTime = HabitTime::create([
            'user_id' => $user->id,
            'habit_id' => 1,
            'start_time' => now(),
            'end_time' => null,
        ]);

        // Arrange: Mock HabitService
        $mockService = Mockery::mock(HabitService::class);
        $mockService->shouldReceive('saveHabitTransaction')
            ->once()
            ->with($habitTime->id, $user->id, $user->timezone ?? 'UTC', 'on')
            ->andReturn(false);

        // App::instance method binds the mock into the service container
        $this->app->instance(HabitService::class, $mockService);

        // Act: Call the store method on the controller
        $controller = new HabitTimeController;
        $response = $controller->store($user->id, $habitTime->id, 'on', $mockService);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->status());
        $this->assertEquals('Habit not added', $response->getData()->message);
    }
}
