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

    public function test_timezone_affects_today_calculation(): void
    {
        // Create user in a timezone far ahead (e.g., Tokyo, UTC+9)
        $userTokyo = \App\Models\User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habitTokyo = \App\Models\Habit::factory()->create(['user_id' => $userTokyo->id]);

        // Create user in a timezone far behind (e.g., Honolulu, UTC-10)
        $userHonolulu = \App\Models\User::factory()->create(['timezone' => 'Pacific/Honolulu']);
        $habitHonolulu = \App\Models\Habit::factory()->create(['user_id' => $userHonolulu->id]);

        // Freeze time to a specific UTC time where the local date differs
        // Example: 2026-03-08 22:00:00 UTC
        // Tokyo time will be 2026-03-09 07:00:00 (Tomorrow)
        // Honolulu time will be 2026-03-08 12:00:00 (Today)
        \Carbon\Carbon::setTestNow('2026-03-08 22:00:00');

        $tokyoToday = '2026-03-09';
        $honoluluToday = '2026-03-08';

        // Tokyo user completes habit for their "today"
        $this->actingAs($userTokyo)->post("/habits/{$habitTokyo->id}/toggle", [
            'date' => $tokyoToday,
        ]);
        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitTokyo->id,
            'completed_date' => $tokyoToday . ' 00:00:00',
        ]);

        // Honolulu user completes habit for their "today"
        $this->actingAs($userHonolulu)->post("/habits/{$habitHonolulu->id}/toggle", [
            'date' => $honoluluToday,
        ]);
        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitHonolulu->id,
            'completed_date' => $honoluluToday . ' 00:00:00',
        ]);

        // Assert streaks are 1 for both (since they completed it on their respective "today")
        $this->assertEquals(1, $habitTokyo->fresh()->getStreaks()['current']);
        $this->assertEquals(1, $habitHonolulu->fresh()->getStreaks()['current']);

        // Assert Tokyo completing on Honolulu's today (yesterday for Tokyo) counts as active streak of 1
        $habitTokyo->completions()->delete();
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habitTokyo->id, 'completed_date' => $honoluluToday]);
        $this->assertEquals(1, $habitTokyo->fresh()->getStreaks()['current']);

        // Assert Tokyo completing on the day before Honolulu's today (2 days ago for Tokyo) breaks streak
        $habitTokyo->completions()->delete();
        \App\Models\HabitCompletion::factory()->create(['habit_id' => $habitTokyo->id, 'completed_date' => '2026-03-07']);
        $this->assertEquals(0, $habitTokyo->fresh()->getStreaks()['current']);
    }
}
