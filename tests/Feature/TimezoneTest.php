<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\HabitCompletion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_accepts_timezone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'timezone' => 'Asia/Tokyo',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');
        $this->assertEquals('Asia/Tokyo', $user->fresh()->timezone);
    }

    public function test_habit_completion_defaults_to_user_timezone(): void
    {
        // Set server time to a known point where UTC and Asia/Tokyo are on different days.
        // E.g., UTC is 2026-03-08 22:00:00 (March 8), Asia/Tokyo is 2026-03-09 07:00:00 (March 9)
        Carbon::setTestNow(Carbon::parse('2026-03-08 22:00:00', 'UTC'));

        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        $expectedTokyoDate = '2026-03-09';

        $response = $this->actingAs($user)->post("/habits/{$habit->id}/toggle");

        $response->assertRedirect();

        // Ensure the completion was recorded for the Tokyo date, NOT the UTC date
        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habit->id,
            'completed_date' => $expectedTokyoDate . ' 00:00:00',
        ]);

        $this->assertDatabaseMissing('habit_completions', [
            'habit_id' => $habit->id,
            'completed_date' => '2026-03-08 00:00:00',
        ]);

        Carbon::setTestNow(); // Reset time
    }

    public function test_dashboard_displays_correct_days_for_timezone(): void
    {
        // UTC is 2026-03-08 22:00:00, Tokyo is 2026-03-09 07:00:00
        Carbon::setTestNow(Carbon::parse('2026-03-08 22:00:00', 'UTC'));

        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        // Since it's March 9th in Tokyo, we should see '9' in the view,
        // representing "today's" day number.
        $response->assertSee('9');
        $response->assertSee('8');
        $response->assertSee('3'); // 9, 8, 7, 6, 5, 4, 3 are the 7 days

        Carbon::setTestNow();
    }

    public function test_streaks_calculate_correctly_based_on_timezone(): void
    {
        // UTC is 2026-03-08 22:00:00, Tokyo is 2026-03-09 07:00:00
        Carbon::setTestNow(Carbon::parse('2026-03-08 22:00:00', 'UTC'));

        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        // Complete habits for Tokyo's "yesterday" (March 8) and "day before yesterday" (March 7)
        HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => '2026-03-08']);
        HabitCompletion::factory()->create(['habit_id' => $habit->id, 'completed_date' => '2026-03-07']);

        $streaks = $habit->fresh()->getStreaks();

        // Even though it's March 8th UTC, it's March 9th in Tokyo.
        // A completion on March 8th is "yesterday" for Tokyo, so the streak should still be active (current = 2).
        $this->assertEquals(2, $streaks['current']);

        Carbon::setTestNow();
    }
}
