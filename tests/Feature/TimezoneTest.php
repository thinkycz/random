<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_timezone_in_profile()
    {
        $user = \App\Models\User::factory()->create(['timezone' => 'UTC']);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'timezone' => 'Asia/Tokyo',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');

        $this->assertEquals('Asia/Tokyo', $user->fresh()->timezone);
    }

    public function test_habits_dashboard_respects_timezone()
    {
        // Set fixed current time to easily test timezone differences
        // Let's say it's 2026-03-08 01:00:00 UTC
        // In UTC, it's the 8th.
        // In Asia/Tokyo (UTC+9), it's 2026-03-08 10:00:00.
        // In America/Los_Angeles (UTC-8), it's 2026-03-07 17:00:00.
        Carbon::setTestNow('2026-03-08 01:00:00');

        $userUtc = \App\Models\User::factory()->create(['timezone' => 'UTC']);
        $habitUtc = \App\Models\Habit::factory()->create(['user_id' => $userUtc->id]);
        $responseUtc = $this->actingAs($userUtc)->get('/dashboard');
        // Check for '8' for the current day in UTC
        $responseUtc->assertSee('>8<', false); // Looking for the day number 8

        $userLa = \App\Models\User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habitLa = \App\Models\Habit::factory()->create(['user_id' => $userLa->id]);
        $responseLa = $this->actingAs($userLa)->get('/dashboard');
        // Check for '7' for the current day in LA
        $responseLa->assertSee('>7<', false); // Looking for the day number 7

        Carbon::setTestNow(); // Reset
    }

    public function test_habit_toggle_respects_timezone()
    {
        Carbon::setTestNow('2026-03-08 01:00:00');

        $userLa = \App\Models\User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habitLa = \App\Models\Habit::factory()->create(['user_id' => $userLa->id]);

        $responseLa = $this->actingAs($userLa)->post("/habits/{$habitLa->id}/toggle"); // no date given, defaults to today in timezone

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitLa->id,
            'completed_date' => '2026-03-07 00:00:00', // 2026-03-07 is the local date in LA
        ]);

        Carbon::setTestNow(); // Reset
    }

    public function test_streak_calculation_respects_timezone()
    {
        Carbon::setTestNow('2026-03-08 01:00:00');

        $userLa = \App\Models\User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habitLa = \App\Models\Habit::factory()->create(['user_id' => $userLa->id]);

        // Yesterday in LA is 2026-03-06. Today is 2026-03-07
        // If we add a completion for 2026-03-06, it should count as yesterday and have a streak of 1.
        \App\Models\HabitCompletion::factory()->create([
            'habit_id' => $habitLa->id,
            'completed_date' => '2026-03-06'
        ]);

        $streaksLa = $habitLa->fresh()->getStreaks();
        $this->assertEquals(1, $streaksLa['current']);
        $this->assertEquals(1, $streaksLa['longest']);

        // If we add a completion for 2026-03-07, it should be today
        \App\Models\HabitCompletion::factory()->create([
            'habit_id' => $habitLa->id,
            'completed_date' => '2026-03-07'
        ]);

        $streaksLa = $habitLa->fresh()->getStreaks();
        $this->assertEquals(2, $streaksLa['current']);
        $this->assertEquals(2, $streaksLa['longest']);

        Carbon::setTestNow(); // Reset
    }
}
