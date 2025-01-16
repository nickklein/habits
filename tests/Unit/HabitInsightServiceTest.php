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
use NickKlein\Habits\Seeders\HabitUserTableSeeder;

class HabitInsightServiceTest extends TestCase
{
    protected $habitUser;

    public function setUp(): void
    {
        parent::setUp();

        $faker = Faker::create();
        $user = User::create([
            'name' => $faker->name,
            'email' => $faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'remember_token' => '23123', 
        ]);
        $this->habitUser = HabitUser::create([
            'habit_id' => $habit->habit_id,
            'user_id' => $user->id ?? 1,
            'habit_id' => $faker->numberBetween(1, 8),
            'streak_time_goal' => $faker->numberBetween(60 * 5, 3600 * 5),
            'streak_time_type' => $faker->randomElement(['daily', 'weekly', 'monthly']),
            'streak_type' => $faker->randomElement(['time', 'count']),
        ]);
        $this->habitUser = HabitUser::factory()->create(['user_id' => $user->id]);
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testgetDailySummaryHighlights()
    {
        // Arrange
        $yesterdaySeconds = 3600 * 2; // Checking for hours version
        $dayBeforeYesterdaySeconds = 1800; // Checking for minutes version
        $yesterdayCollection = new Collection([['total_duration' => $yesterdaySeconds]]);
        $dayBeforeYesterdayCollection = new Collection([['total_duration' => $dayBeforeYesterdaySeconds]]);
        $yesterday = Carbon::yesterday()->hour(0);
        $dayBeforeYesterday = Carbon::today()->subDays(2)->hour(0);

        $mockHabitService = $this->createMock(HabitService::class);
        $mockHabitInsightRepository = $this->createMock(HabitInsightRepository::class);

        $mockHabitInsightRepository->method('getDailyTotalsByHabitId')
            ->willReturnOnConsecutiveCalls($yesterdayCollection, $dayBeforeYesterdayCollection);


        $mockHabitService->method('convertSecondsToMinutesOrHoursV2')
            ->willReturnOnConsecutiveCalls(
                ['value' => $yesterdaySeconds / 60 / 60, 'unit' => 'hrs', 'unit_full' => 'hours'],
                ['value' => $dayBeforeYesterdaySeconds, 'unit' => 'min', 'unit_full' => 'minutes']
            );

        $habitInsightService = new HabitInsightService(); // Assuming that HabitIsnightService is the service class name

        $result = $habitInsightService->getDailySummaryHighlights($this->habitUser, $mockHabitService, $mockHabitInsightRepository);

        $this->assertEquals('You did 90 more minutes yesterday than you did the day before', $result['description']);

        $this->assertEquals($yesterdaySeconds / 60 / 60, $result['barOne']['number']);
        $this->assertEquals('hours', $result['barOne']['unit']);
        $this->assertEquals($yesterday->dayName, $result['barOne']['bar_text']);
        $this->assertEquals(100, $result['barOne']['width']);

        $this->assertEquals($dayBeforeYesterdaySeconds, $result['barTwo']['number']);
        $this->assertEquals('minutes', $result['barTwo']['unit']);
        $this->assertEquals($dayBeforeYesterday->dayName, $result['barTwo']['bar_text']);
        $this->assertEquals(25, $result['barTwo']['width']);
    }

    public function testgetWeeklyAverageHighlights()
    {
        // Set up mocked dependencies and method parameters
        $habitId = 1;
        $userId = 1;

        // Mock HabitService
        $mockHabitService = $this->createMock(HabitService::class);
        $mockHabitService->method('convertSecondsToMinutesOrHoursV2')
            ->willReturnOnConsecutiveCalls(
                ['value' => 20000, 'unit' => 'hrs', 'unit_full' => 'hours'],
                ['value' => 90, 'unit' => 'min', 'unit_full' => 'minutes']
            );

        // Mock HabitInsightRepository
        $habitInsightRepository = $this->createMock(HabitInsightRepository::class);
        $habitInsightRepository->method('getAveragesByHabitId')
            ->willReturnOnConsecutiveCalls(300, 200);

        // Instantiate the service to test with mocked dependencies
        $habitInsightService = new HabitInsightService();

        // Call the method to test
        $result = $habitInsightService->getWeeklyAverageHighlights($this->habitUser, $mockHabitService, $habitInsightRepository);

        // Define your assertions
        $this->assertEquals(20000, $result['barOne']['number']);
        $this->assertEquals(90, $result['barTwo']['number']);
        $this->assertEquals('hours / day', $result['barOne']['unit']);
        $this->assertEquals('minutes / day', $result['barTwo']['unit']);
    }


    public function testgetMonthlyAverageHighlights()
    {
        // Set up mocked dependencies and method parameters
        $habitId = 1;
        $userId = 1;

        // Mock HabitService
        $mockHabitService = $this->createMock(HabitService::class);
        $mockHabitService->method('convertSecondsToMinutesOrHoursV2')
            ->willReturnOnConsecutiveCalls(
                ['value' => 20000, 'unit' => 'hrs', 'unit_full' => 'hours'],
                ['value' => 90, 'unit' => 'min', 'unit_full' => 'minutes']
            );

        // Mock HabitInsightRepository
        $habitInsightRepository = $this->createMock(HabitInsightRepository::class);
        $habitInsightRepository->method('getAveragesByHabitId')
            ->willReturnOnConsecutiveCalls(300, 200);

        // Instantiate the service to test with mocked dependencies
        $habitInsightService = new HabitInsightService();

        // Call the method to test
        $result = $habitInsightService->getMonthlyAverageHighlights($this->habitUser, $mockHabitService, $habitInsightRepository);

        // Define your assertions
        $this->assertEquals(20000, $result['barOne']['number']);
        $this->assertEquals(90, $result['barTwo']['number']);
        $this->assertEquals('hours / day', $result['barOne']['unit']);
        $this->assertEquals('minutes / day', $result['barTwo']['unit']);
    }

    public function testGetYearlySummaryHighlights()
    {
        // Set up mocked dependencies and method parameters
        $habitId = 1;
        $userId = 1;

        // Mock HabitService
        $mockHabitService = $this->createMock(HabitService::class);
        $mockHabitService->method('convertSecondsToMinutesOrHoursV2')
            ->willReturnOnConsecutiveCalls(
                ['value' => 20000, 'unit' => 'hrs', 'unit_full' => 'hours'],
                ['value' => 90, 'unit' => 'min', 'unit_full' => 'minutes']
            );

        // Mock HabitInsightRepository
        $habitInsightRepository = $this->createMock(HabitInsightRepository::class);
        $habitInsightRepository->method('getSummationByHabitId')
            ->willReturnOnConsecutiveCalls(300, 200);

        // Instantiate the service to test with mocked dependencies
        $habitInsightService = new HabitInsightService();

        // Call the method to test
        $result = $habitInsightService->getYearlySummaryHighlights($this->habitUser, $mockHabitService, $habitInsightRepository);

        // Define your assertions
        $this->assertEquals(20000, $result['barOne']['number']);
        $this->assertEquals(90, $result['barTwo']['number']);
        $this->assertEquals('hours', $result['barOne']['unit']);
        $this->assertEquals('minutes', $result['barTwo']['unit']);
    }
}
