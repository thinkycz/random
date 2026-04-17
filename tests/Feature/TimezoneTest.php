<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_habits_respect_user_timezone(): void
    {
        // A user in Tokyo (UTC+9)
        $tokyoUser = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $tokyoHabit = Habit::factory()->create(['user_id' => $tokyoUser->id]);

        // A user in Honolulu (UTC-10)
        $honoluluUser = User::factory()->create(['timezone' => 'Pacific/Honolulu']);
        $honoluluHabit = Habit::factory()->create(['user_id' => $honoluluUser->id]);

        $tokyoToday = now('Asia/Tokyo')->format('Y-m-d');
        $honoluluToday = now('Pacific/Honolulu')->format('Y-m-d');

        // Toggle habit for Tokyo user (defaults to their today)
        $this->actingAs($tokyoUser)->post("/habits/{$tokyoHabit->id}/toggle");

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $tokyoHabit->id,
            'completed_date' => $tokyoToday . ' 00:00:00',
        ]);

        // Toggle habit for Honolulu user (defaults to their today)
        $this->actingAs($honoluluUser)->post("/habits/{$honoluluHabit->id}/toggle");

        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $honoluluHabit->id,
            'completed_date' => $honoluluToday . ' 00:00:00',
        ]);
    }
}
