<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    public function dashboard()
    {
        $userTimezone = auth()->user()->timezone ?? 'UTC';
        $today = now()->setTimezone($userTimezone);

        $habits = auth()->user()->habits()->with(['category', 'completions' => function ($query) use ($today) {
            $query->where('completed_date', '>=', $today->copy()->subDays(6)->format('Y-m-d'));
        }])->get();

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'name' => $date->format('D'),
                'day' => $date->format('j'),
            ];
        }

        $habits->each(function ($habit) use ($days) {
            $completions = $habit->completions->pluck('completed_date')->map(fn($date) => $date->format('Y-m-d'))->toArray();
            $completions_by_day = [];
            foreach ($days as $day) {
                $completions_by_day[$day['date']] = in_array($day['date'], $completions);
            }
            $habit->setAttribute('completions_by_day', $completions_by_day);
        });

        return view('dashboard', compact('habits', 'days', 'userTimezone'));
    }

    public function index()
    {
        $habits = auth()->user()->habits()->with(['category', 'completions'])->latest()->get();
        return view('habits.index', compact('habits'));
    }

    public function create()
    {
        $categories = auth()->user()->categories()->orderBy('name')->get();
        return view('habits.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if (!empty($validated['category_id'])) {
            $category = \App\Models\Category::findOrFail($validated['category_id']);
            if ($category->user_id !== auth()->id()) abort(403);
        }

        auth()->user()->habits()->create($validated);

        return redirect()->route('habits.index')->with('success', 'Habit created successfully.');
    }

    public function edit(Habit $habit)
    {
        if ($habit->user_id !== auth()->id()) abort(403);

        $categories = auth()->user()->categories()->orderBy('name')->get();
        return view('habits.edit', compact('habit', 'categories'));
    }

    public function update(Request $request, Habit $habit)
    {
        if ($habit->user_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if (!empty($validated['category_id'])) {
            $category = \App\Models\Category::findOrFail($validated['category_id']);
            if ($category->user_id !== auth()->id()) abort(403);
        }

        $habit->update($validated);

        return redirect()->route('habits.index')->with('success', 'Habit updated successfully.');
    }

    public function destroy(Habit $habit)
    {
        if ($habit->user_id !== auth()->id()) abort(403);

        $habit->delete();

        return redirect()->route('habits.index')->with('success', 'Habit deleted successfully.');
    }

    public function toggle(Request $request, Habit $habit)
    {
        if ($habit->user_id !== auth()->id()) abort(403);

        $userTimezone = auth()->user()->timezone ?? 'UTC';
        $date = $request->input('date', now()->setTimezone($userTimezone)->format('Y-m-d'));

        // Since completed_date is cast to a date, it stores as a datetime with 00:00:00 in SQLite/MySQL.
        // For querying, it's safer to use date casting or query by date.
        $completion = $habit->completions()->whereDate('completed_date', $date)->first();

        if ($completion) {
            $completion->delete();
            $message = 'Habit marked as incomplete.';
        } else {
            $habit->completions()->create(['completed_date' => $date]);
            $message = 'Habit marked as complete!';
        }

        return back()->with('success', $message);
    }
}
