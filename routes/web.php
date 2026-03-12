<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [\App\Http\Controllers\HabitController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('categories', \App\Http\Controllers\CategoryController::class);
    Route::resource('habits', \App\Http\Controllers\HabitController::class);
    Route::post('/habits/{habit}/toggle', [\App\Http\Controllers\HabitController::class, 'toggle'])->name('habits.toggle');
    Route::patch('/habits/{habit}/archive', [\App\Http\Controllers\HabitController::class, 'archive'])->name('habits.archive');
    Route::patch('/habits/{habit}/unarchive', [\App\Http\Controllers\HabitController::class, 'unarchive'])->name('habits.unarchive');
});

require __DIR__.'/auth.php';
