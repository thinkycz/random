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

    public function test_timezone_logic()
    {
        // Set mock time: May 5th, 2026 23:00:00 UTC
        \Carbon\Carbon::setTestNow('2026-05-05 23:00:00');

        // User A is in UTC (it is May 5th for them)
        $userA = \App\Models\User::factory()->create(['timezone' => 'UTC']);
        $habitA = \App\Models\Habit::factory()->create(['user_id' => $userA->id]);

        // User B is in Asia/Tokyo (UTC+9) (it is May 6th, 08:00:00 for them)
        $userB = \App\Models\User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habitB = \App\Models\Habit::factory()->create(['user_id' => $userB->id]);

        // Verify "today" calculated for the dashboard toggle
        $responseA = $this->actingAs($userA)->post("/habits/{$habitA->id}/toggle");
        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitA->id,
            'completed_date' => '2026-05-05 00:00:00', // Today in UTC
        ]);

        $responseB = $this->actingAs($userB)->post("/habits/{$habitB->id}/toggle");
        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitB->id,
            'completed_date' => '2026-05-06 00:00:00', // Today in Asia/Tokyo
        ]);

        // Verify streak calculations
        // For User A, they completed it on their "today" (May 5th)
        $streaksA = $habitA->fresh()->getStreaks();
        $this->assertEquals(1, $streaksA['current']);

        // For User B, they completed it on their "today" (May 6th)
        $streaksB = $habitB->fresh()->getStreaks();
        $this->assertEquals(1, $streaksB['current']);
    }
}
