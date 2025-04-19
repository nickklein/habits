<?php

namespace NickKlein\Habits\Tests;

use NickKlein\Habits\Tests\TestModels\User;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Controllers\HabitTimeController;
use NickKlein\Habits\Requests\HabitTimeRequests;
use NickKlein\Habits\Services\HabitService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Mockery;
use NickKlein\Habits\Tests\TestCase;


class HabitTimeControllerTest extends TestCase
{
    public function testDestroyMethodSuccessfully()
    {
        // Arrange: Create a user and a habit for the user
        $user = User::find(1);
        $habitTime = HabitTime::where('user_id', $user->id)->first();
        $service = Mockery::mock(HabitService::class);
        $this->app->instance(HabitService::class, $service);

        // Assume the user can delete the habit
        $service
            ->shouldReceive('deleteHabitTime')
            ->once()
            ->with($habitTime->id, $user->id)
            ->andReturn(true);

        // Act: Call the destroy method on the controller
        $this->actingAs($user);
        $controller = $this->app->make(HabitTimeController::class);
        $response = $controller->destroy($habitTime->id, $service);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('Habit deleted successfully', $response->getData()->message);
    }

    public function testDestroyMethodFailed()
    {
        // Arrange: Create a user and a habit not owned by the user
        $user = User::find(1);
        $habitTime = HabitTime::where('user_id', $user->id)->first();
        $service = Mockery::mock(HabitService::class);
        $this->app->instance(HabitService::class, $service);

        // Assume the user cannot delete the habit
        $service
            ->shouldReceive('deleteHabitTime')
            ->with($habitTime->id, $user->id)
            ->andReturn(false);

        // Act: Call the destroy method on the controller
        $controller = new HabitTimeController;
        $controller = $this->app->make(HabitTimeController::class);
        $this->actingAs($user);
        $response = $controller->destroy($habitTime->id, $service);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('Habit not deleted', $response->getData()->message);
    }

    public function testUpdateHabitTimeFailedValidation()
    {
        $user = User::find(1);
        $this->actingAs($user);

        $habitTime = HabitTime::where('user_id', $user->id)->first();

        $response = $this->put(route('habits.transactions.update', $habitTime->id), []);

        $response->assertSessionHasErrors(['habit_id', 'start_time', 'end_time']);
    }
    /**/
    /* /** */
    /*  *  Test if test update habit time failed */
    /*  */
    public function testUpdateHabitTimeFailed()
    {
        // Arrange: Create a user and a habit not owned by the user
        //
        $user = User::find(1);
        $timezone = 'America/Los_Angeles';
        $this->actingAs($user);
        $habitTime = HabitTime::where('user_id', $user->id)->first();

        $startDate = Carbon::parse($habitTime->start_time)->setTimezone($timezone)->format('Y-m-d');
        $startTime = Carbon::parse($habitTime->start_time)->setTimezone($timezone)->format('H:i:s');
        $endDate = Carbon::parse($habitTime->end_time)->setTimezone($timezone)->format('Y-m-d');
        $endTime = Carbon::parse($habitTime->end_time)->setTimezone($timezone)->format('H:i:s');

        $service = Mockery::mock(HabitService::class);
        $this->app->instance(HabitService::class, $service);
        $service
            ->shouldReceive('updateHabitTime')
            ->with($habitTime->id, $user->id, $user->timezone, $habitTime->habit_id, $startDate, $startTime, $endDate, $endTime)
            ->andReturn(false);


        $controller = $this->app->make(HabitTimeController::class);
        $requests = Mockery::mock(HabitTimeRequests::class);
        $requests->shouldReceive('validated')->once()->andReturn([
            'habit_id' => $habitTime->habit_id,
            'start_date' => $startDate,
            'start_time' => $startTime,
            'end_date' => $endDate,
            'end_time' => $endTime,
        ]);

        $response = $controller->updateHabitTimes($habitTime->id, $requests, $service);
        // Check the session message
        $flashMessage = $response->getSession()->get('message');
        $this->assertEquals(__('Habit not updated'), $flashMessage);
    }

    /**
     *  Test if test update habit time successfully saved
    **/
    public function testUpdateHabitTimeSuccess()
    {
        // Arrange: Create a user and a habit not owned by the user
        $user = User::find(1);
        $this->actingAs($user);
        $habitTime = HabitTime::where('id', $user->id)->first();

        $startDate = Carbon::parse($habitTime->start_time)->format('Y-m-d');
        $startTime = Carbon::parse($habitTime->start_time)->format('H:i:s');
        $endDate = Carbon::parse($habitTime->end_time)->format('Y-m-d');
        $endTime = Carbon::parse($habitTime->end_time)->format('H:i:s');

        $service = Mockery::mock(HabitService::class);
        $this->app->instance(HabitService::class, $service);
        $service
            ->shouldReceive('updateHabitTime')
            ->with($habitTime->id, $user->id, $user->timezone, $habitTime->habit_id, $startDate, $startTime, $endDate, $endTime)
            ->andReturn(true);

        $controller = $this->app->make(HabitTimeController::class);
        $requests = Mockery::mock(HabitTimeRequests::class);
        $requests->shouldReceive('validated')->once()->andReturn([
            'habit_id' => $habitTime->habit_id,
            'start_date' => $startDate,
            'start_time' => $startTime,
            'end_date' => $endDate,
            'end_time' => $endTime,
        ]);

        $response = $controller->updateHabitTimes($habitTime->id, $requests, $service);
        // Check the session message
        $flashMessage = $response->getSession()->get('message');
        $this->assertEquals(__('Habit updated successfully'), $flashMessage);
    }

    /** 
    * Tests working validation for add habit time
    *
    * @return void
    * @dataProvider invalidHabitTimeProvider
    **/
    public function testAddHabitTimeFailedValidation($data, $errorField)
    {
        $user = User::find(1);
        $this->actingAs($user);

        $response = $this->post(route('habits.transactions.store'), $data);

        $response->assertSessionHasErrors($errorField);
    }
    /**/
    /**
     * Habit Time Provider for invalid data
     *
     * @param [type] $data
     * @return void
     **/
    public function invalidHabitTimeProvider()
    {
        return [
            'Missing habit_id' => [
                'data' => [
                    // 'habit_id' is missing
                    'start_date' => date('Y-m-d'),
                    'start_time' => '09:00',
                    'end_date' => date('Y-m-d'),
                    'end_time' => '17:00',
                ],
                'errorField' => 'habit_id'
            ],
            'Invalid habit_id' => [
                'data' => [
                    'habit_id' => 'invalid', // Not an integer
                    'start_date' => date('Y-m-d'),
                    'start_time' => '09:00',
                    'end_date' => date('Y-m-d'),
                    'end_time' => '17:00',
                ],
                'errorField' => 'habit_id'
            ],
            'Missing start_date' => [
                'data' => [
                    'habit_id' => 1,
                    // 'start_date' is missing
                    'start_time' => '09:00',
                    'end_date' => date('Y-m-d'),
                    'end_time' => '17:00',
                ],
                'errorField' => 'start_date'
            ],
            'Invalid start_date' => [
                'data' => [
                    'habit_id' => 1,
                    'start_date' => 'invalid', // Not a date
                    'start_time' => '09:00',
                    'end_date' => date('Y-m-d'),
                    'end_time' => '17:00',
                ],
                'errorField' => 'start_date'
            ],
            'Missing end_date' => [
                'data' => [
                    'habit_id' => 1,
                    'start_date' => date('Y-m-d'),
                    'start_time' => '09:00',
                    // 'end_date' is missing
                    'end_time' => '17:00',
                ],
                'errorField' => 'end_date'
            ],
            'Invalid end_date' => [
                'data' => [
                    'habit_id' => 1,
                    'start_date' => date('Y-m-d'),
                    'start_time' => '09:00',
                    'end_date' => 'invalid', // Not a date
                    'end_time' => '17:00',
                ],
                'errorField' => 'end_date'
            ],
            'Missing start_time' => [
                'data' => [
                    'habit_id' => 1,
                    'start_date' => date('Y-m-d'),
                    // 'start_time' is missing
                    'end_date' => date('Y-m-d'),
                    'end_time' => '17:00',
                ],
                'errorField' => 'start_time'
            ],
            'Missing end_time' => [
                'data' => [
                    'habit_id' => 1,
                    'start_date' => date('Y-m-d'),
                    'start_time' => '09:00',
                    'end_date' => date('Y-m-d'),
                    // 'end_time' is missing
                ],
                'errorField' => 'end_time'
            ],
        ];
    }


     /**
      * Validate the add habit timer
      * 
      * @return void
      **/
    public function testAddHabitTimerFailedValidation()
    {
        $user = User::find(1);
        $this->actingAs($user);

        $response = $this->post(route('habits.transactions.timer.store'), []);

        $response->assertSessionHasErrors(['habit_id']);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
