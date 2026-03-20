<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HabitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_user_can_view_habits_on_dashboard(): void
    {
        $user = \App\Models\User::factory()->create();
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee($habit->name);
        $response->assertSee('Last 7 Days');
        $response->assertSee(now()->format('j')); // today's day number
    }

    public function test_user_can_create_habit(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->post('/habits', [
            'name' => 'Drink Water',
            'description' => '2 liters a day',
        ]);

        $response->assertRedirect('/habits');
        $this->assertDatabaseHas('habits', [
            'user_id' => $user->id,
            'name' => 'Drink Water',
        ]);
    }

    public function test_user_can_toggle_habit_completion(): void
    {
        $user = \App\Models\User::factory()->create();
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        $today = now()->format('Y-m-d');

        // Complete
        $response = $this->actingAs($user)->post("/habits/{$habit->id}/toggle", [
            'date' => $today,
        ]);

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habit->id,
            'completed_date' => $today . ' 00:00:00',
        ]);

        // Uncomplete
        $response = $this->actingAs($user)->post("/habits/{$habit->id}/toggle", [
            'date' => $today,
        ]);

        $this->assertDatabaseMissing('habit_completions', [
            'habit_id' => $habit->id,
            'completed_date' => $today . ' 00:00:00',
        ]);
    }

    public function test_streak_calculation()
    {
        $user = \App\Models\User::factory()->create();
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        // No completions
        $streaks = $habit->getStreaks();
        $this->assertEquals(0, $streaks['current']);
        $this->assertEquals(0, $streaks['longest']);

        // 3 day streak ending yesterday
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => now()->subDay()->format('Y-m-d')]);
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => now()->subDays(2)->format('Y-m-d')]);
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => now()->subDays(3)->format('Y-m-d')]);

        $streaks = $habit->fresh()->getStreaks();
        $this->assertEquals(3, $streaks['current']);
        $this->assertEquals(3, $streaks['longest']);

        // Missed today, so current streak is 3 (yesterday still counts as active).
        // Let's also miss yesterday
        $habit->completions()->delete();
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => now()->subDays(2)->format('Y-m-d')]);
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => now()->subDays(3)->format('Y-m-d')]);

        $streaks = $habit->fresh()->getStreaks();
        $this->assertEquals(0, $streaks['current']);
        $this->assertEquals(2, $streaks['longest']);

        // View habits index page to ensure it loads
        $response = $this->actingAs($user)->get(route('habits.index'));
        $response->assertStatus(200);
        $response->assertSee('2</span> days', false); // Longest streak
    }

    public function test_timezone_respects_user_setting(): void
    {
        // Let's create a user in a specific timezone
        $user = \App\Models\User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        // Mock current time so we can reliably test timezone differences
        // Let's say it's currently 23:00 UTC on Jan 1
        // In Tokyo (UTC+9), it will be 08:00 on Jan 2
        \Carbon\Carbon::setTestNow('2026-01-01 23:00:00');

        $tokyoToday = \Carbon\Carbon::now()->timezone('Asia/Tokyo')->format('Y-m-d');
        $this->assertEquals('2026-01-02', $tokyoToday);

        // Toggle completion using default request (should use the user's timezone)
        // Wait, the toggle endpoint takes a 'date' from the request, but if not provided, it uses `now()->timezone($userTimezone)->format('Y-m-d')`
        $response = $this->actingAs($user)->post("/habits/{$habit->id}/toggle");

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habit->id,
            'completed_date' => $tokyoToday . ' 00:00:00',
        ]);

        \Carbon\Carbon::setTestNow(); // reset
    }

    public function test_streak_calculation_respects_timezone()
    {
        $user = \App\Models\User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        // Mock current time: Jan 1 23:00:00 UTC
        // Tokyo time is Jan 2 08:00:00
        \Carbon\Carbon::setTestNow('2026-01-01 23:00:00');

        // Completion on Jan 1 Tokyo time
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => '2026-01-01']);

        // Completion on Jan 2 Tokyo time (Today in Tokyo)
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => '2026-01-02']);

        $streaks = $habit->fresh()->getStreaks();
        $this->assertEquals(2, $streaks['current']);

        // Now if we change timezone to UTC, Jan 2 is tomorrow, not today.
        // Today is Jan 1. Yesterday was Dec 31.
        // The last completion is Jan 2 (in the future).
        // Since Jan 2 > Jan 1, current streak logic might evaluate it differently. Let's see.
        // Wait, current streak logic:
        // completions = [2026-01-02, 2026-01-01]
        // today = 2026-01-01
        // yesterday = 2025-12-31
        // completions[0] !== today and !== yesterday, so activeStreakDate is null.
        // currentStreak will be 0.
        $user->update(['timezone' => 'UTC']);
        $streaks = $habit->fresh()->getStreaks();
        $this->assertEquals(0, $streaks['current']); // Because completion is in the future for UTC

        \Carbon\Carbon::setTestNow();
    }
}
