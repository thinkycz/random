<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $healthCategory = \App\Models\Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Health',
            'color' => '#10b981', // emerald-500
        ]);

        $learningCategory = \App\Models\Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Learning',
            'color' => '#3b82f6', // blue-500
        ]);

        $drinkWaterHabit = \App\Models\Habit::factory()->create([
            'user_id' => $user->id,
            'category_id' => $healthCategory->id,
            'name' => 'Drink 2L Water',
            'description' => 'Stay hydrated throughout the day.',
        ]);

        $readBookHabit = \App\Models\Habit::factory()->create([
            'user_id' => $user->id,
            'category_id' => $learningCategory->id,
            'name' => 'Read 30 minutes',
            'description' => 'Read non-fiction or fiction books.',
        ]);

        $stretchHabit = \App\Models\Habit::factory()->create([
            'user_id' => $user->id,
            'category_id' => $healthCategory->id,
            'name' => 'Stretch',
        ]);

        // Seed some past completions
        $today = now();
        for ($i = 0; $i < 7; $i++) {
            $date = $today->copy()->subDays($i)->format('Y-m-d');

            // Randomly complete habits in the past
            if (rand(0, 1)) {
                \App\Models\HabitCompletion::factory()->create([
                    'habit_id' => $drinkWaterHabit->id,
                    'completed_date' => $date,
                ]);
            }
            if (rand(0, 1)) {
                \App\Models\HabitCompletion::factory()->create([
                    'habit_id' => $readBookHabit->id,
                    'completed_date' => $date,
                ]);
            }
        }
    }
}
