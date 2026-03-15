<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Habit extends Model
{
    /** @use HasFactory<\Database\Factories\HabitFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function completions()
    {
        return $this->hasMany(HabitCompletion::class);
    }

    public function getStreaks()
    {
        // Pluck the dates, unique them, sort descending, and convert to array of strings
        $completions = $this->completions
            ->pluck('completed_date')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        if (empty($completions)) {
            return ['current' => 0, 'longest' => 0];
        }

        $currentStreak = 0;
        $longestStreak = 0;

        // Use the user's timezone to determine the "today" boundary.
        // If not running in a request with an authenticated user, fallback to UTC or the habit's owner.
        // Since we have the relationship, we can use $this->user->timezone.
        $timezone = $this->user->timezone ?? 'UTC';

        $today = now()->timezone($timezone)->format('Y-m-d');
        $yesterday = now()->timezone($timezone)->subDay()->format('Y-m-d');

        $i = 0;
        $activeStreakDate = null;

        if ($completions[0] === $today) {
            $activeStreakDate = now()->timezone($timezone);
        } elseif ($completions[0] === $yesterday) {
            $activeStreakDate = now()->timezone($timezone)->subDay();
        }

        if ($activeStreakDate) {
            while ($i < count($completions) && $completions[$i] === $activeStreakDate->format('Y-m-d')) {
                $currentStreak++;
                $activeStreakDate->subDay();
                $i++;
            }
        }

        $tempStreak = 1;
        $longestStreak = 1;

        for ($j = 0; $j < count($completions) - 1; $j++) {
            $current = \Carbon\Carbon::parse($completions[$j]);
            $next = \Carbon\Carbon::parse($completions[$j + 1]);

            if ($current->copy()->subDay()->format('Y-m-d') === $next->format('Y-m-d')) {
                $tempStreak++;
                if ($tempStreak > $longestStreak) {
                    $longestStreak = $tempStreak;
                }
            } else {
                $tempStreak = 1;
            }
        }

        return [
            'current' => $currentStreak,
            'longest' => max($longestStreak, $currentStreak)
        ];
    }
}
