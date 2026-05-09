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
        // User in a timezone far behind UTC
        $user = \App\Models\User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habit = \App\Models\Habit::factory()->create(['user_id' => $user->id]);

        // It is 1 AM on Jan 2nd in UTC
        // But it is 5 PM on Jan 1st in America/Los_Angeles
        \Carbon\Carbon::setTestNow('2026-01-02 01:00:00');

        // User completes habit
        \App\Models\HabitCompletion::factory()->create([
            'habit_id' => $habit->id,
            'completed_date' => now('America/Los_Angeles')->format('Y-m-d')
        ]);

        // Assert the stored date is Jan 1st
        $this->assertEquals('2026-01-01 00:00:00', $habit->completions()->first()->completed_date);

        // Check streaks. It should see the completion as "today" (Jan 1st locally)
        $streaks = $habit->fresh()->getStreaks();
        $this->assertEquals(1, $streaks['current']);
        $this->assertEquals(1, $streaks['longest']);

        \Carbon\Carbon::setTestNow(); // reset
    }
}
