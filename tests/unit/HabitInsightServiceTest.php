<?php

namespace Tests\Unit;

use App\Models\Habit;
use App\Models\HabitUser;
use App\Models\User;
use App\Repositories\HabitInsightRepository;
use App\Services\HabitInsightService;
use App\Services\HabitService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HabitInsightServiceTest extends TestCase
{
    protected $habitUser;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
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
