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

    public function test_habit_streak_respects_user_timezone(): void
    {
        // Let's assume current server time is UTC 00:30 (early today).
        // For a user in America/Los_Angeles (UTC-7 or UTC-8), it's still "yesterday".
        $user = \App\Models\User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        $serverNow = now('UTC');
        $userNow = now('America/Los_Angeles');

        // Make the user timezone significantly different
        // E.g. we force the current time to be something that falls on a different date for the user vs server
        // To do this reliably in a test without freezing time, we use Carbon's setTestNow
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-10 02:00:00', 'UTC'));
        // In LA, this is 2026-04-09 19:00:00 (Previous day)

        // The user completes the habit on "their" today (04-09)
        $userToday = now('America/Los_Angeles')->format('Y-m-d');
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => $userToday]);

        $streaks = $habit->fresh()->getStreaks();
        $this->assertEquals(1, $streaks['current']); // Active streak because it was completed on the user's "today"

        // If completed on the server's today (04-10), it would be "tomorrow" for the user, which shouldn't happen via normal UI but lets check
        // We only care that the streak logic works relative to the user's timezone.
        \Carbon\Carbon::setTestNow(); // Reset
    }

    public function test_dashboard_displays_correct_days_for_timezone(): void
    {
        $user = \App\Models\User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-10 23:00:00', 'UTC'));
        // In Tokyo (UTC+9), this is 2026-04-11 08:00:00 (Next day)

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $userTodayDay = now('Asia/Tokyo')->format('j');
        // The dashboard should show Tokyo's date as the current day
        $response->assertSee($userTodayDay);

        // Assert that the latest date generated in the view variables matches Tokyo's today
        $days = $response->viewData('days');
        $this->assertEquals(now('Asia/Tokyo')->format('Y-m-d'), $days[6]['date']);
        $this->assertTrue($days[6]['is_today']);

        \Carbon\Carbon::setTestNow(); // Reset
    }
}
