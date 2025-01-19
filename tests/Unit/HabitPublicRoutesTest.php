<?php

namespace NickKlein\Habits\Tests;

use NickKlein\Habits\Controllers\Public\HabitTimeController;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Tests\TestModels\User;
use NickKlein\Habits\Repositories\HabitInsightRepository;
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
            ->with($user->id)
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
            ->with($user->id)
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

        // Create a habit
        $habitTime = HabitTime::where('user_id', $user->id)->first();

        // Arrange: Mock HabitInsightRepository
        $habitInsightRepository = new HabitInsightRepository();

        // Arrange: Mock HabitInsightService
        $mockService = Mockery::mock(HabitInsightService::class);
        $mockService->shouldReceive('manageHabitTime')
            ->once()
            ->with($habitTime->id, $user->id, 'on', $habitInsightRepository)
            ->andReturn(true);

        // App::instance method binds the mock into the service container
        // So when Laravel tries to resolve HabitService class, it gets the mock instance instead
        $this->app->instance(HabitInsightService::class, $mockService);

        // Act: Call the store method on the controller
        $controller = new HabitTimeController;
        $response = $controller->store($user->id, $habitTime->id, 'on', $mockService, $habitInsightRepository);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->status());
        $this->assertEquals('Habit time added successfully', $response->getData()->message);
    }

    public function testStoreHabitTimeFail()
    {
        // Arrange: Create a user
        $user = User::find(1);

        // Create a habit
        $habitTime = HabitTime::where('user_id', $user->id)->first();

        // Insight Repository
        $habitInsightRepositoryMock = Mockery::mock(HabitInsightRepository::class);

        // Arrange: Mock HabitService
        $mockService = Mockery::mock(HabitInsightService::class);
        $mockService->shouldReceive('manageHabitTime')
            ->once()
            ->with($habitTime->id, $user->id, 'on', $habitInsightRepositoryMock)
            ->andReturn(false);

        // App::instance method binds the mock into the service container
        // So when Laravel tries to resolve HabitService class, it gets the mock instance instead
        $this->app->instance(HabitInsightService::class, $mockService);

        // Act: Call the store method on the controller
        $controller = new HabitTimeController;
        $response = $controller->store($user->id, $habitTime->id, 'on', $mockService, $habitInsightRepositoryMock);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->status());
        $this->assertEquals('Habit time not added', $response->getData()->message);
    }
}
