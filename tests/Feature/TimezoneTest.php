<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Habit;
use Carbon\Carbon;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_differs_based_on_user_timezone(): void
    {
        // Set test now to UTC Midnight (00:00:00) of March 10th
        // A user in Tokyo (UTC+9) will be on March 10th 09:00:00
        // A user in Los Angeles (UTC-8) will be on March 9th 16:00:00
        Carbon::setTestNow('2026-03-10 00:00:00');

        $userTokyo = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $habitTokyo = Habit::factory()->create(['user_id' => $userTokyo->id]);

        $userLA = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habitLA = Habit::factory()->create(['user_id' => $userLA->id]);

        // Tokyo user requests dashboard
        $responseTokyo = $this->actingAs($userTokyo)->get('/dashboard');
        $responseTokyo->assertStatus(200);

        // Assert Tokyo sees March 10th as the latest date
        $responseTokyo->assertSee('value="2026-03-10"', false);
        $responseTokyo->assertDontSee('value="2026-03-11"', false);

        // LA user requests dashboard
        $responseLA = $this->actingAs($userLA)->get('/dashboard');
        $responseLA->assertStatus(200);

        // Assert LA sees March 9th as the latest date
        $responseLA->assertSee('value="2026-03-09"', false);
        $responseLA->assertDontSee('value="2026-03-10"', false);

        Carbon::setTestNow(); // Reset
    }

    public function test_toggling_habit_respects_user_timezone(): void
    {
        Carbon::setTestNow('2026-03-10 00:00:00');

        $userLA = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $habitLA = Habit::factory()->create(['user_id' => $userLA->id]);

        // Toggle habit without passing explicit date (simulating a default fallback)
        $this->actingAs($userLA)->post("/habits/{$habitLA->id}/toggle");

        // The default date in toggle() uses now($timezone)->format('Y-m-d')
        // In LA, it is currently March 9th.
        $this->assertDatabaseHas('habit_completions', [
            'habit_id' => $habitLA->id,
            'completed_date' => '2026-03-09 00:00:00',
        ]);

        Carbon::setTestNow(); // Reset
    }
}
