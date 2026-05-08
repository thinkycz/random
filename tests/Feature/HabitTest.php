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

    public function test_timezone_day_boundaries()
    {
        // Imagine right now is 2026-03-08 02:00:00 UTC
        \Carbon\Carbon::setTestNow('2026-03-08 02:00:00');

        // User 1 is in Tokyo (UTC+9), so it's 2026-03-08 11:00:00 local
        $userTokyo = \App\Models\User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habitTokyo = \App\Models\Habit::factory()->create(['user_id' => $userTokyo->id]);

        // Mark habit complete for "today" in Tokyo
        $this->actingAs($userTokyo)->post("/habits/{$habitTokyo->id}/toggle", [
            'date' => now($userTokyo->timezone)->format('Y-m-d') // '2026-03-08'
        ]);

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitTokyo->id,
            'completed_date' => '2026-03-08 00:00:00',
        ]);

        // User 2 is in Los Angeles (UTC-8), so it's 2026-03-07 18:00:00 local
        $userLA = \App\Models\User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habitLA = \App\Models\Habit::factory()->create(['user_id' => $userLA->id]);

        // Mark habit complete for "today" in LA
        $this->actingAs($userLA)->post("/habits/{$habitLA->id}/toggle", [
            'date' => now($userLA->timezone)->format('Y-m-d') // '2026-03-07'
        ]);

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitLA->id,
            'completed_date' => '2026-03-07 00:00:00',
        ]);

        // Check streaks for LA user. The last completion was 2026-03-07.
        // Today for LA is 2026-03-07. So the active streak is 1.
        $streaksLA = $habitLA->fresh()->getStreaks();
        $this->assertEquals(1, $streaksLA['current']);

        // Check streaks for Tokyo user. The last completion was 2026-03-08.
        // Today for Tokyo is 2026-03-08. So the active streak is 1.
        $streaksTokyo = $habitTokyo->fresh()->getStreaks();
        $this->assertEquals(1, $streaksTokyo['current']);
    }
}
