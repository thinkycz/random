<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    public function dashboard()
    {
        $habits = auth()->user()->habits()->with('category')->get();
        $today = now()->format('Y-m-d');

        $habits->each(function ($habit) use ($today) {
            $habit->is_completed_today = $habit->completions()->where('completed_date', $today)->exists();
        });

        return view('dashboard', compact('habits'));
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

        $completion = $habit->completions()->where('completed_date', $date)->first();

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
