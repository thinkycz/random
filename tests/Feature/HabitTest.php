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

    public function test_streak_calculation_respects_timezone()
    {
        // Create user in a specific timezone
        $user = \App\Models\User::factory()->create(['timezone' => 'Asia/Tokyo']); // UTC+9
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        // Freeze time to a specific UTC time where the day is different in Tokyo
        // e.g., 2026-03-08 22:00:00 UTC is 2026-03-09 07:00:00 in Tokyo
        $knownDate = \Carbon\Carbon::parse('2026-03-08 22:00:00', 'UTC');
        \Carbon\Carbon::setTestNow($knownDate);

        // In Tokyo, "today" is '2026-03-09'
        // "yesterday" is '2026-03-08'

        // Let's create a completion for '2026-03-08'
        // This is yesterday in Tokyo
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => '2026-03-08']);

        $streaks = $habit->fresh()->getStreaks();

        // Since the completion is "yesterday" (Tokyo time), the current streak is 1 and it's active
        $this->assertEquals(1, $streaks['current']);

        // Now if the user's timezone was Pacific/Midway (UTC-11), it would be 2026-03-08 11:00:00
        // "today" is '2026-03-08'
        // So a completion on '2026-03-08' is "today" for Midway.
        $userMidway = \App\Models\User::factory()->create(['timezone' => 'Pacific/Midway']);
        $habitMidway = \App\Models\Habit::factory()->create(['user_id' => $userMidway->id]);
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habitMidway->id, 'completed_date' => '2026-03-08']);

        $streaksMidway = $habitMidway->fresh()->getStreaks();

        // Since the completion is "today" (Midway time), the current streak is 1
        $this->assertEquals(1, $streaksMidway['current']);

        \Carbon\Carbon::setTestNow(); // Reset time
    }
}
