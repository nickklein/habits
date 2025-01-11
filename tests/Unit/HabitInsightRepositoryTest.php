<?php

namespace NickKlein\Habits\Tests;

use NickKlein\Habits\Models\HabitTime;
use NickKlein\Habits\Repositories\HabitInsightRepository;
use Carbon\Carbon;
use NickKlein\Habits\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HabitInsightRepositoryTest extends TestCase
{

    use RefreshDatabase; // this will ensure that the database is reset after each test

    /** @test */
    public function it_can_fetch_the_average_duration_by_habit_id()
    {
        $userId = 1;
        $habitId = 1;

        $startOfRange = Carbon::today()->subDays(6);
        $endOfRange = Carbon::today();

        // Let's seed some data to test against
        HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $habitId,
            'start_time' => Carbon::today()->subDays(5),
            'duration' => 60, // 60 seconds
        ]);

        HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $habitId,
            'start_time' => Carbon::today()->subDays(4),
            'duration' => 180, // 180 seconds
        ]);

        HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $habitId,
            'start_time' => Carbon::today()->subDays(3),
            'duration' => 120, // 120 seconds
        ]);

        $repository = new HabitInsightRepository();

        // Expected average: (60 + 180 + 120) / 3 = 120 seconds
        $average = $repository->getAveragesByHabitId($userId, [$habitId], $startOfRange, $endOfRange);

        $this->assertEquals(120, $average);
    }

    /** @test */
    public function it_can_fetch_the_summation_duration_by_habit_id()
    {
        $userId = 1;
        $habitId = 1;

        $startOfRange = Carbon::today()->subDays(6);
        $endOfRange = Carbon::today();

        // Let's seed some data to test against
        HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $habitId,
            'start_time' => Carbon::today()->subDays(5),
            'duration' => 60, // 60 seconds
        ]);

        HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $habitId,
            'start_time' => Carbon::today()->subDays(4),
            'duration' => 180, // 180 seconds
        ]);

        HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $habitId,
            'start_time' => Carbon::today()->subDays(3),
            'duration' => 120, // 120 seconds
        ]);

        $repository = new HabitInsightRepository();

        // Expected summation: 60 + 180 + 120 = 360 seconds
        $sum = $repository->getSummationByHabitId($userId, [$habitId], $startOfRange, $endOfRange);

        $this->assertEquals(360, $sum);
    }

    /** @test */
    public function it_checks_if_any_habit_is_active_for_a_given_user()
    {
        $userId = 1;
        $habitId = 1;

        // Initially, no habit is active
        $repository = new HabitInsightRepository();
        $this->assertFalse($repository->anyHabitActive($userId));

        // Create a habit that is currently active (has a start_time but no end_time)
        HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $habitId,
            'start_time' => Carbon::now(),
            'end_time' => null,
        ]);

        $this->assertTrue($repository->anyHabitActive($userId));

        // Let's make the habit inactive by adding an end time
        HabitTime::where('user_id', $userId)->update(['end_time' => Carbon::now()]);

        $this->assertFalse($repository->anyHabitActive($userId));
    }

    /** @test */
    public function it_checks_if_a_specific_habit_is_active_for_a_given_user()
    {
        $userId = 1;
        $activeHabitId = 1;
        $inactiveHabitId = 2;

        $repository = new HabitInsightRepository();

        // Initially, the habit is not active
        $this->assertFalse($repository->isHabitActive($activeHabitId, $userId));

        // Create an active habit entry (has a start_time but no end_time)
        HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $activeHabitId,
            'start_time' => Carbon::now(),
            'end_time' => null,
        ]);

        $this->assertTrue($repository->isHabitActive($activeHabitId, $userId));

        // Create an inactive habit entry (has both a start_time and an end_time)
        HabitTime::create([
            'user_id' => $userId,
            'habit_id' => $inactiveHabitId,
            'start_time' => Carbon::now()->subHours(1),
            'end_time' => Carbon::now(),
        ]);

        $this->assertFalse($repository->isHabitActive($inactiveHabitId, $userId));
    }


    /** @test */
    public function it_fetches_the_habit_duration_by_date_range()
    {
        $userId = 1;
        $habitId = 1;
        $startRange = Carbon::today()->subDays(6)->toDateString();
        $endRange = Carbon::today()->toDateString();

        $repository = new HabitInsightRepository();

        // Seed habit entries for testing
        HabitTime::insert([
            [
                'user_id' => $userId,
                'habit_id' => $habitId,
                'start_time' => Carbon::today()->subDays(5),
                'end_time' => Carbon::today()->subDays(5)->addSeconds(60),
                'duration' => 60,
            ],
            [
                'user_id' => $userId,
                'habit_id' => $habitId,
                'start_time' => Carbon::today()->subDays(4),
                'end_time' => Carbon::today()->subDays(4)->addSeconds(180),
                'duration' => 180,
            ],
            [
                'user_id' => $userId,
                'habit_id' => $habitId,
                'start_time' => Carbon::today()->subDays(3),
                'end_time' => Carbon::today()->subDays(3)->addSeconds(120),
                'duration' => 120,
            ]
        ]);


        $results = $repository->getDailyTotalsByHabitId($userId, [$habitId], $startRange, $endRange);

        $this->assertCount(3, $results); // We expect 3 records for the seeded data

        $this->assertEquals(60, $results[0]->total_duration);
        $this->assertEquals(180, $results[1]->total_duration);
        $this->assertEquals(120, $results[2]->total_duration);
    }
}
