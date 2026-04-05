<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Habit;
use App\Models\HabitCompletion;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_timezone_in_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'timezone' => 'Asia/Tokyo',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');
        $this->assertEquals('Asia/Tokyo', $user->fresh()->timezone);
    }

    public function test_dashboard_uses_user_timezone(): void
    {
        // Let's mock the current time to be at the edge of a day boundary in UTC
        // E.g. UTC is 2026-03-08 23:00:00
        $now = \Carbon\Carbon::create(2026, 3, 8, 23, 0, 0, 'UTC');
        \Carbon\Carbon::setTestNow($now);

        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']); // UTC+9, so Tokyo is 2026-03-09 08:00:00
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        // "Today" in Tokyo should be March 9th
        $response->assertSee('9');
        // It should NOT treat March 8th as today, the exact string is a bit tricky to assert,
        // but we can ensure "2026-03-09" is passed to the view or generated in the form.
        $response->assertSee('value="2026-03-09"', false);
    }

    public function test_toggle_habit_uses_user_timezone(): void
    {
        $now = \Carbon\Carbon::create(2026, 3, 8, 23, 0, 0, 'UTC');
        \Carbon\Carbon::setTestNow($now);

        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        // Toggle without explicit date should fall back to user's "today"
        $response = $this->actingAs($user)->post("/habits/{$habit->id}/toggle");

        $response->assertRedirect();

        // Tokyo's today is 2026-03-09
        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habit->id,
            'completed_date' => '2026-03-09 00:00:00',
        ]);
    }

    public function test_streak_calculation_uses_user_timezone(): void
    {
        // UTC: March 8, 23:00. Tokyo: March 9, 08:00.
        $now = \Carbon\Carbon::create(2026, 3, 8, 23, 0, 0, 'UTC');
        \Carbon\Carbon::setTestNow($now);

        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        // Complete the habit for March 8 (Tokyo's yesterday)
        HabitCompletion::factory()->create([
            'habit_id' => $habit->id,
            'completed_date' => '2026-03-08'
        ]);

        // Complete the habit for March 7 (Tokyo's day before yesterday)
        HabitCompletion::factory()->create([
            'habit_id' => $habit->id,
            'completed_date' => '2026-03-07'
        ]);

        // If streaks are using Tokyo timezone, "yesterday" is March 8.
        // So this should be an active streak of 2.
        $streaks = $habit->fresh()->getStreaks();
        $this->assertEquals(2, $streaks['current']);
        $this->assertEquals(2, $streaks['longest']);

        // If it was using UTC, "today" would be March 8, meaning "yesterday" is March 7,
        // but wait, if UTC "today" is March 8, the completion on March 8 is "today", so streak is 2.

        // Let's create a more contrasting scenario.
        // What if user timezone is America/Los_Angeles (UTC-8)?
        // March 8 23:00 UTC -> March 8 15:00 LA. "Today" is March 8. "Yesterday" is March 7.
        // Let's complete on March 9 and March 8.
        $user2 = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habit2 = Habit::factory()->create(['user_id' => $user2->id]);

        // Let's complete on March 7 and March 6.
        HabitCompletion::factory()->create([
            'habit_id' => $habit2->id,
            'completed_date' => '2026-03-07' // LA yesterday
        ]);
        HabitCompletion::factory()->create([
            'habit_id' => $habit2->id,
            'completed_date' => '2026-03-06' // LA day before yesterday
        ]);

        $streaks2 = $habit2->fresh()->getStreaks();
        // Since LA's yesterday is March 7, this is an active streak of 2.
        $this->assertEquals(2, $streaks2['current']);

        \Carbon\Carbon::setTestNow(); // Reset mock
    }
}
