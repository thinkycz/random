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

    public function test_timezone_aware_streaks()
    {
        // Let's create a scenario where UTC time is "tomorrow" relative to a specific timezone.
        // For instance, if UTC is 2026-03-08 02:00:00
        // Pacific/Honolulu (-10) would be 2026-03-07 16:00:00

        \Carbon\Carbon::setTestNow('2026-03-08 02:00:00');

        $user = \App\Models\User::factory()->create(['timezone' => 'Pacific/Honolulu']);
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        // In Honolulu, "today" is 2026-03-07.
        // So a completion on 2026-03-07 should count as "today".
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => '2026-03-07']);

        $streaks = $habit->fresh()->getStreaks();

        // Because "today" is 2026-03-07 in Honolulu, and we completed it on 2026-03-07, the current streak should be 1.
        $this->assertEquals(1, $streaks['current']);
        $this->assertEquals(1, $streaks['longest']);

        // Now let's test if a user with UTC timezone gets the correct streak.
        // For UTC, "today" is 2026-03-08.
        $userUtc = \App\Models\User::factory()->create(['timezone' => 'UTC']);
        $habitUtc = \App\Models\Habit::factory()->create(['user_id' => $userUtc->id]);

        // Completing on 2026-03-07 means "yesterday" for UTC.
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habitUtc->id, 'completed_date' => '2026-03-07']);

        $streaksUtc = $habitUtc->fresh()->getStreaks();

        // Since it was yesterday in UTC, the streak is still active (1).
        $this->assertEquals(1, $streaksUtc['current']);

        // Now if we have a completion on 2026-03-06 (two days ago for UTC), the streak breaks.
        \App\Models\HabitCompletion::where('habit_id', $habitUtc->id)->delete();
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habitUtc->id, 'completed_date' => '2026-03-06']);

        $streaksUtcBroken = $habitUtc->fresh()->getStreaks();
        $this->assertEquals(0, $streaksUtcBroken['current']);

        // Reset Carbon mock
        \Carbon\Carbon::setTestNow();
    }
}
