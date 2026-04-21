<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_timezone_affects_habit_dashboard_today(): void
    {
        // Let's create two users in different timezones: Pacific/Apia (UTC+13) and Pacific/Midway (UTC-11)
        // This is a 24-hour difference, so they will always have different "Today" dates
        // However, we don't know when the test will run, so we just test one of them explicitly.

        // For testing we will mock the current time to be at a boundary.
        // Let's set the server time to exactly 00:01:00 UTC on 2026-05-01.
        $serverTime = \Carbon\Carbon::create(2026, 5, 1, 0, 1, 0, 'UTC');
        \Carbon\Carbon::setTestNow($serverTime);

        // At 00:01 UTC on May 1st:
        // Pacific/Midway (UTC-11) is 13:01 on April 30th.
        $user = \App\Models\User::factory()->create([
            'timezone' => 'Pacific/Midway',
        ]);
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        // The day rendered as "Today" (bolded) should be the 30th, not the 1st
        // And the date used in the form input for today should be 2026-04-30
        $response->assertSee('value="2026-04-30"', false);
        $response->assertDontSee('value="2026-05-01"', false);

        // Clean up test now
        \Carbon\Carbon::setTestNow();
    }

    public function test_streak_calculation_respects_timezone()
    {
        // Set time to 2026-05-01 00:01:00 UTC
        $serverTime = \Carbon\Carbon::create(2026, 5, 1, 0, 1, 0, 'UTC');
        \Carbon\Carbon::setTestNow($serverTime);

        $user = \App\Models\User::factory()->create([
            'timezone' => 'Pacific/Midway', // It is April 30th here
        ]);
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        // Complete the habit on April 28 and April 29
        \App\Models\HabitCompletion::factory()->create([
            'habit_id' => $habit->id,
            'completed_date' => '2026-04-28'
        ]);
        \App\Models\HabitCompletion::factory()->create([
            'habit_id' => $habit->id,
            'completed_date' => '2026-04-29'
        ]);

        // April 30 is "today" for this user.
        // So the streak should be 2 (yesterday and day before).
        $streaks = $habit->fresh()->getStreaks();
        $this->assertEquals(2, $streaks['current']);

        \Carbon\Carbon::setTestNow();
    }
}
