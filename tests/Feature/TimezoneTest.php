<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Habit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_habit_completion_uses_user_timezone(): void
    {
        $user = User::factory()->create([
            'timezone' => 'America/New_York', // UTC-5 (or -4 in daylight saving)
        ]);

        $habit = Habit::factory()->create([
            'user_id' => $user->id,
        ]);

        // Mock time to be just past midnight in UTC, which means it is STILL the previous day in New York.
        // e.g., 2026-05-20 02:00:00 UTC is 2026-05-19 22:00:00 in America/New_York (if EDT).
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 2, 0, 0, 'UTC'));

        $response = $this->actingAs($user)->post(route('habits.toggle', $habit));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // The completion should be recorded for 2026-05-19 because of the user's timezone.
        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habit->id,
            'completed_date' => '2026-05-19 00:00:00', // Date casting uses 00:00:00
        ]);

        Carbon::setTestNow(); // reset
    }

    public function test_habit_streak_calculation_respects_user_timezone(): void
    {
        $user = User::factory()->create([
            'timezone' => 'Asia/Tokyo', // UTC+9
        ]);

        $habit = Habit::factory()->create([
            'user_id' => $user->id,
        ]);

        // Set completed habits.
        // Let's say today is 2026-05-20 in Tokyo.
        // 2026-05-20 05:00:00 in Tokyo is 2026-05-19 20:00:00 UTC.
        // If the server was UTC, "today" might still be 19th.

        // Let's set test now to 2026-05-19 22:00:00 UTC.
        // In Tokyo (UTC+9), the time is 2026-05-20 07:00:00.
        Carbon::setTestNow(Carbon::create(2026, 5, 19, 22, 0, 0, 'UTC'));

        // If they completed it on 2026-05-18 and 2026-05-19,
        // and today is 2026-05-20 in their timezone,
        // then the streak should be broken (or current streak = 0) unless they complete it today.

        $habit->completions()->create(['completed_date' => '2026-05-18']);
        $habit->completions()->create(['completed_date' => '2026-05-19']);

        $streaks = $habit->getStreaks();

        // Since today is the 20th in Tokyo, and they haven't completed it on the 20th,
        // and they DID complete it on the 19th (yesterday), the active streak should still be 2.

        $this->assertEquals(2, $streaks['current']);
        $this->assertEquals(2, $streaks['longest']);

        Carbon::setTestNow(); // reset
    }
}
