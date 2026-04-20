<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_habit_completion_uses_user_timezone()
    {
        // Setup user in Tokyo (+09:00)
        $userTokyo = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habitTokyo = Habit::factory()->create(['user_id' => $userTokyo->id]);

        // Setup user in LA (-08:00)
        $userLA = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habitLA = Habit::factory()->create(['user_id' => $userLA->id]);

        // Server time: 2026-04-20 20:00:00 UTC
        // Tokyo time: 2026-04-21 05:00:00
        // LA time: 2026-04-20 12:00:00
        Carbon::setTestNow('2026-04-20 20:00:00');

        $this->actingAs($userTokyo)->post("/habits/{$habitTokyo->id}/toggle", [
            'date' => now($userTokyo->timezone)->format('Y-m-d')
        ]);

        $this->actingAs($userLA)->post("/habits/{$habitLA->id}/toggle", [
            'date' => now($userLA->timezone)->format('Y-m-d')
        ]);

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitTokyo->id,
            'completed_date' => '2026-04-21 00:00:00', // Because they completed it on their "today" which is the 21st
        ]);

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitLA->id,
            'completed_date' => '2026-04-20 00:00:00', // Because they completed it on their "today" which is the 20th
        ]);

        Carbon::setTestNow();
    }
}
