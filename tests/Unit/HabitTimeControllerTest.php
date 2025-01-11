<?php

namespace NickKlein\Habits\Tests;

use NickKlein\Habits\Tests\TestModels\User;
use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Controllers\HabitTimeController;
use NickKlein\Habits\Requests\HabitTimeRequests;
use NickKlein\Habits\Services\HabitService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Mockery;
use NickKlein\Habits\Tests\TestCase;


class HabitTimeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testDestroyMethodSuccessfully()
    {
        // Arrange: Create a user and a habit for the user
        $user = User::factory()->create();
        $habitTime = HabitTime::factory()->create(['user_id' => $user->id]);
        $service = $this->createMock(HabitService::class);

        // Assume the user can delete the habit
        $service->expects($this->once())
            ->method('deleteHabitTime')
            ->with($habitTime->id, $user->id)
            ->willReturn(true);

        // Act: Call the destroy method on the controller
        $controller = new HabitTimeController;
        $this->actingAs($user);
        $response = $controller->destroy($habitTime->id, $service);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('Habit deleted successfully', $response->getData()->message);
    }

    public function testDestroyMethodFailed()
    {
        // Arrange: Create a user and a habit not owned by the user
        $user = User::factory()->create();
        $habitTime = HabitTime::factory()->create(['user_id' => $user->id]);
        $service = $this->createMock(HabitService::class);

        // Assume the user cannot delete the habit
        $service->expects($this->once())
            ->method('deleteHabitTime')
            ->with($habitTime->id, $user->id)
            ->willReturn(false);

        // Act: Call the destroy method on the controller
        $controller = new HabitTimeController;
        $this->actingAs($user);
        $response = $controller->destroy($habitTime->id, $service);

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('Habit not deleted', $response->getData()->message);
    }

    public function testUpdateHabitTimeFailedValidation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $habitTime = HabitTime::factory()->create(['user_id' => $user->id]);

        $response = $this->put(route('habits.transactions.update', $habitTime->id), []);

        $response->assertSessionHasErrors(['habit_id', 'start_time', 'end_time']);
    }

    /**
     *  Test if test update habit time failed
     */
    public function testUpdateHabitTimeFailed()
    {
        // Arrange: Create a user and a habit not owned by the user
        $user = User::factory()->create();
        $this->actingAs($user);
        $habitTime = HabitTime::factory()->create(['user_id' => $user->id]);

        $startDate = Carbon::parse($habitTime->start_time)->format('Y-m-d');
        $startTime = Carbon::parse($habitTime->start_time)->format('H:i:s');
        $endDate = Carbon::parse($habitTime->end_time)->format('Y-m-d');
        $endTime = Carbon::parse($habitTime->end_time)->format('H:i:s');

        $service = $this->createMock(HabitService::class);
        $service->expects($this->once())
            ->method('updateHabitTime')
            ->with($habitTime->id, $user->id, $habitTime->habit_id, $startDate, $startTime, $endDate, $endTime)
            ->willReturn(false);

        $controller = new HabitTimeController;
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
     */
    public function testUpdateHabitTimeSuccess()
    {
        // Arrange: Create a user and a habit not owned by the user
        $user = User::factory()->create();
        $this->actingAs($user);
        $habitTime = HabitTime::factory()->create(['user_id' => $user->id]);

        $startDate = Carbon::parse($habitTime->start_time)->format('Y-m-d');
        $startTime = Carbon::parse($habitTime->start_time)->format('H:i:s');
        $endDate = Carbon::parse($habitTime->end_time)->format('Y-m-d');
        $endTime = Carbon::parse($habitTime->end_time)->format('H:i:s');

        $service = $this->createMock(HabitService::class);
        $service->expects($this->once())
            ->method('updateHabitTime')
            ->with($habitTime->id, $user->id, $habitTime->habit_id, $startDate, $startTime, $endDate, $endTime)
            ->willReturn(true);

        $controller = new HabitTimeController;
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
     */
    public function testAddHabitTimeFailedValidation($data, $errorField)
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('habits.transactions.store'), $data);

        $response->assertSessionHasErrors($errorField);
    }

    /**
     * Habit Time Provider for invalid data
     *
     * @param [type] $data
     * @return void
     */
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
     */
    public function testAddHabitTimerFailedValidation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('habits.transactions.timer.store'), []);

        $response->assertSessionHasErrors(['habit_id']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
