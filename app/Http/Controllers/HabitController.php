<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    public function dashboard()
    {
        $habits = auth()->user()->habits()->with(['category', 'completions'])->get();

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->isToday() ? 'Today' : $date->format('D'),
                'day' => $date->format('d'),
            ];
        }

        $habits->each(function ($habit) use ($days) {
            $completions_by_date = [];
            foreach ($days as $day) {
                $completions_by_date[$day['date']] = $habit->completions
                    ->where('completed_date', '>=', \Carbon\Carbon::parse($day['date'])->startOfDay())
                    ->where('completed_date', '<=', \Carbon\Carbon::parse($day['date'])->endOfDay())
                    ->isNotEmpty();
            }
            $habit->setAttribute('completions_by_date', $completions_by_date);
        });

        return view('dashboard', compact('habits', 'days'));
    }

    public function index()
    {
        $habits = auth()->user()->habits()->with('category')->latest()->get();
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

        $date = $request->input('date', now()->format('Y-m-d'));

        $completion = $habit->completions()->whereDate('completed_date', $date)->first();

        if ($completion) {
            $completion->delete();
            $message = 'Habit marked as incomplete.';
        } else {
            $habit->completions()->create(['completed_date' => $date . ' 00:00:00']);
            $message = 'Habit marked as complete!';
        }

        return back()->with('success', $message);
    }
}
