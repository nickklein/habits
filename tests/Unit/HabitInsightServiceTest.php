<?php

namespace NickKlein\Habits\Tests;

use NickKlein\Habits\Models\Habit;
use NickKlein\Habits\Models\HabitUser;
use NickKlein\Habits\Tests\TestModels\User;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use NickKlein\Habits\Services\HabitInsightService;
use NickKlein\Habits\Services\HabitService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use NickKlein\Habits\Tests\TestCase;
use Faker\Factory as Faker;
use Mockery;
use NickKlein\Habits\Seeders\HabitUserTableSeeder;
use NickKlein\Habits\Services\HabitTypeFactory;
use NickKlein\Habits\Services\TimeHabitHandler;

class HabitInsightServiceTest extends TestCase
{
    protected $habitUser;

    /* public function setUp(): void */
    /* { */
    /*     parent::setUp(); */
    /**/
    /*     $faker = Faker::create(); */
    /*     $user = User::create([ */
    /*         'name' => $faker->name, */
    /*         'email' => $faker->unique()->safeEmail, */
    /*         'email_verified_at' => now(), */
    /*         'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', */
    /*         'remember_token' => '23123',  */
    /*     ]); */
    /*     $this->habitUser = HabitUser::create([ */
    /*         'habit_id' => $habit->habit_id, */
    /*         'user_id' => $user->id ?? 1, */
    /*         'habit_id' => $faker->numberBetween(1, 8), */
    /*         'streak_goal' => $faker->numberBetween(60 * 5, 3600 * 5), */
    /*         'streak_time_type' => $faker->randomElement(['daily', 'weekly', 'monthly']), */
    /*         'streak_type' => $faker->randomElement(['time', 'count']), */
    /*     ]); */
    /*     $this->habitUser = HabitUser::factory()->create(['user_id' => $user->id]); */
    /* } */
    /**/

    /**
     * A basic unit test example.
     *
     * @return void
    **/
    public function testgetDailySummaryHighlights()
    {
        // Arrange
        $habitUser = HabitUser::find(1);
        $habitUser->habit_type = 'time'; // Ensure we have a valid habit type for the handler
        
        $yesterdaySeconds = 3600 * 2; // 2 hours in seconds
        $dayBeforeYesterdaySeconds = 1800; // 30 minutes in seconds
        
        // Create proper collection objects that match what the repository returns
        $yesterdayObj = new \stdClass();
        $yesterdayObj->total_duration = $yesterdaySeconds;
        $yesterdayCollection = new Collection([$yesterdayObj]);
        
        $dayBeforeYesterdayObj = new \stdClass();
        $dayBeforeYesterdayObj->total_duration = $dayBeforeYesterdaySeconds;
        $dayBeforeYesterdayCollection = new Collection([$dayBeforeYesterdayObj]);
        
        // Mock the repository
        $mockHabitInsightRepository = Mockery::mock(HabitInsightRepository::class);
        $this->app->instance(HabitInsightRepository::class, $mockHabitInsightRepository);
        
        // Mock TimeHabitHandler via the factory
        $mockTimeHabitHandler = Mockery::mock(TimeHabitHandler::class);
        $mockHabitTypeFactory = Mockery::mock(HabitTypeFactory::class);
        $this->app->instance(HabitTypeFactory::class, $mockHabitTypeFactory);
        
        // Set up expected repository calls
        $mockHabitInsightRepository->shouldReceive('getDailyTotalsByHabitId')
            ->with($habitUser->user_id, 'UTC', [$habitUser->habit_id], Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($yesterdayCollection);
            
        $mockHabitInsightRepository->shouldReceive('getDailyTotalsByHabitId')
            ->with($habitUser->user_id, 'UTC', [$habitUser->habit_id], Mockery::any(), Mockery::any())
            ->once()
            ->andReturn($dayBeforeYesterdayCollection);
        
        // Set up expected TimeHabitHandler calls via factory
        $mockHabitTypeFactory->shouldReceive('getHandler')
            ->with('time')
            ->andReturn($mockTimeHabitHandler);
            
        $mockTimeHabitHandler->shouldReceive('formatValue')
            ->with($yesterdaySeconds)
            ->once()
            ->andReturn([
                'value' => 2.0,
                'unit' => 'hrs',
                'unit_full' => 'hours'
            ]);
            
        $mockTimeHabitHandler->shouldReceive('formatValue')
            ->with($dayBeforeYesterdaySeconds)
            ->once()
            ->andReturn([
                'value' => 30.0,
                'unit' => 'min',
                'unit_full' => 'minutes'
            ]);
            
        $mockTimeHabitHandler->shouldReceive('calculatePercentageDifference')
            ->with($yesterdaySeconds, $dayBeforeYesterdaySeconds)
            ->once()
            ->andReturn([100, 25]);
            
        $mockTimeHabitHandler->shouldReceive('formatDifference')
            ->with($yesterdaySeconds, $dayBeforeYesterdaySeconds)
            ->once()
            ->andReturn(90);
            
        $mockTimeHabitHandler->shouldReceive('getUnitLabelFull')
            ->once()
            ->andReturn('minutes');

        // Mock HabitService since it's expected as the third parameter
        $mockHabitService = Mockery::mock(HabitService::class);
        $this->app->instance(HabitService::class, $mockHabitService);

        // Call the service method
        $habitInsightService = $this->app->make(HabitInsightService::class);
        $result = $habitInsightService->getDailySummaryHighlights($habitUser, 'UTC', $mockHabitService, $mockHabitInsightRepository);

        // Assert
        $this->assertEquals('You did 90 more minutes yesterday than you did the day before', $result['description']);

        $this->assertEquals(2.0, $result['barOne']['number']);
        $this->assertEquals('hours', $result['barOne']['unit']);
        $this->assertEquals(100, $result['barOne']['width']);

        $this->assertEquals(30.0, $result['barTwo']['number']);
        $this->assertEquals('minutes', $result['barTwo']['unit']);
        $this->assertEquals(25, $result['barTwo']['width']);
        
        // We're not testing the exact day names since they're based on the current day of the week
        $this->assertArrayHasKey('bar_text', $result['barOne']);
        $this->assertArrayHasKey('bar_text', $result['barTwo']);
    }

    public function testgetWeeklyAverageHighlights()
    {
        // Set up mocked dependencies and method parameters
        $habitUser = HabitUser::find(1);
        $habitUser->habit_type = 'time'; // Ensure we have a valid habit type for the handler
        $thisWeekValue = 300; // 5 minutes in seconds
        $lastWeekValue = 200; // 3.3 minutes in seconds
        
        // Mock HabitService (still needed as a parameter)
        $mockHabitService = Mockery::mock(HabitService::class);
        $this->app->instance(HabitService::class, $mockHabitService);

        // Mock HabitInsightRepository
        $mockHabitInsightRepository = Mockery::mock(HabitInsightRepository::class);
        $this->app->instance(HabitInsightRepository::class, $mockHabitInsightRepository);
        
        // Mock TimeHabitHandler via the factory
        $mockTimeHabitHandler = Mockery::mock(TimeHabitHandler::class);
        $mockHabitTypeFactory = Mockery::mock(HabitTypeFactory::class);
        $this->app->instance(HabitTypeFactory::class, $mockHabitTypeFactory);
        
        // Set up repository mock
        $mockHabitInsightRepository->shouldReceive('getAveragesByHabitId')
            ->with($habitUser->user_id, [$habitUser->habit_id], Mockery::any(), Mockery::any(), 7)
            ->once()
            ->andReturn($thisWeekValue);
            
        $mockHabitInsightRepository->shouldReceive('getAveragesByHabitId')
            ->with($habitUser->user_id, [$habitUser->habit_id], Mockery::any(), Mockery::any(), 7)
            ->once()
            ->andReturn($lastWeekValue);
        
        // Set up handler mock
        $mockHabitTypeFactory->shouldReceive('getHandler')
            ->with('time')
            ->andReturn($mockTimeHabitHandler);
            
        $mockTimeHabitHandler->shouldReceive('formatValue')
            ->with($thisWeekValue)
            ->once()
            ->andReturn([
                'value' => 5.0,
                'unit' => 'min',
                'unit_full' => 'minutes'
            ]);
            
        $mockTimeHabitHandler->shouldReceive('formatValue')
            ->with($lastWeekValue)
            ->once()
            ->andReturn([
                'value' => 3.3,
                'unit' => 'min', 
                'unit_full' => 'minutes'
            ]);
            
        $mockTimeHabitHandler->shouldReceive('calculatePercentageDifference')
            ->with($thisWeekValue, $lastWeekValue)
            ->once()
            ->andReturn([100, 67]);
            
        $mockTimeHabitHandler->shouldReceive('formatDifference')
            ->with($lastWeekValue, $thisWeekValue)
            ->once()
            ->andReturn(1.7);
            
        $mockTimeHabitHandler->shouldReceive('getUnitLabelFull')
            ->once()
            ->andReturn('minutes');

        // Instantiate the service to test with mocked dependencies
        $habitInsightService = $this->app->make(HabitInsightService::class);

        // Call the method to test
        $result = $habitInsightService->getWeeklyAverageHighlights($habitUser, 'UTC', $mockHabitService, $mockHabitInsightRepository);

        // Define your assertions
        $this->assertEquals(5.0, $result['barOne']['number']);
        $this->assertEquals(3.3, $result['barTwo']['number']);
        $this->assertEquals('minutes / day', $result['barOne']['unit']);
        $this->assertEquals('minutes / day', $result['barTwo']['unit']);
        $this->assertEquals(100, $result['barOne']['width']);
        $this->assertEquals(67, $result['barTwo']['width']);
        $this->assertEquals("Last week, you averaged 1.7 more minutes than the week before", $result['description']);
        
        // We're not testing exact date formats in the bar_text
        $this->assertArrayHasKey('bar_text', $result['barOne']);
        $this->assertArrayHasKey('bar_text', $result['barTwo']);
    }


    public function testgetMonthlyAverageHighlights()
    {
        // Set up mocked dependencies and method parameters
        $habitUser = HabitUser::find(1);

        // Mock HabitService
        $mockHabitService = Mockery::mock(HabitService::class);
        $this->app->instance(HabitService::class, $mockHabitService);
        $mockHabitService->shouldReceive('convertSecondsToMinutesOrHoursV2')
            ->andReturn(
                ['value' => 20000, 'unit' => 'hrs', 'unit_full' => 'hours'],
                ['value' => 90, 'unit' => 'min', 'unit_full' => 'minutes']
            );

        // Mock HabitInsightRepository
        $habitInsightRepository = Mockery::mock(HabitInsightRepository::class);
        $this->app->instance(HabitInsightRepository::class, $habitInsightRepository);
        $habitInsightRepository->shouldReceive('getAveragesByHabitId')
            ->andReturn(300, 200);

        // Instantiate the service to test with mocked dependencies
        $habitInsightService = $this->app->make(HabitInsightService::class);

        // Call the method to test
        $result = $habitInsightService->getMonthlyAverageHighlights($habitUser, 'UTC', $mockHabitService, $habitInsightRepository);

        // Define your assertions
        $this->assertEquals(20000, $result['barOne']['number']);
        $this->assertEquals(90, $result['barTwo']['number']);
        $this->assertEquals('hours / day', $result['barOne']['unit']);
        $this->assertEquals('minutes / day', $result['barTwo']['unit']);
    }

    public function testGetYearlySummaryHighlights()
    {
        // Set up mocked dependencies and method parameters
        $habitUser = HabitUser::find(1);

        // Mock HabitService
        $mockHabitService = Mockery::mock(HabitService::class);
        $this->app->instance(HabitService::class, $mockHabitService);
        $mockHabitService->shouldReceive('convertSecondsToMinutesOrHoursV2')
            ->andReturn(
                ['value' => 20000, 'unit' => 'hrs', 'unit_full' => 'hours'],
                ['value' => 90, 'unit' => 'min', 'unit_full' => 'minutes']
            );

        // Mock HabitInsightRepository
        $habitInsightRepository = Mockery::mock(HabitInsightRepository::class);
        $this->app->instance(HabitInsightRepository::class, $habitInsightRepository);
        $habitInsightRepository->shouldReceive('getSummationByHabitId')
            ->andReturn(300, 200);

        // Instantiate the service to test with mocked dependencies
        $habitInsightService = $this->app->make(HabitInsightService::class);

        // Call the method to test
        $result = $habitInsightService->getYearlySummaryHighlights($habitUser, 'UTC', $mockHabitService, $habitInsightRepository);

        // Define your assertions
        $this->assertEquals(20000, $result['barOne']['number']);
        $this->assertEquals(90, $result['barTwo']['number']);
        $this->assertEquals('hours', $result['barOne']['unit']);
        $this->assertEquals('minutes', $result['barTwo']['unit']);
    }
}
