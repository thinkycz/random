# Changelog Autonomous

This file must be append-only and contain:
- date
- features added
- files/modules affected
- migrations created
- tests added
- breaking changes if any

---

## March 8, 2026
**Features added:**
- Initialized Laravel 12 project.
- Installed Laravel Breeze (Blade stack) for authentication.
- Defined Product Concept: **Habit Tracker**.
- Implemented CRUD for Categories (with custom colors).
- Implemented CRUD for Habits.
- Built a Dashboard to view today's habits and toggle their completion status.
- Configured a comprehensive `DatabaseSeeder` to generate a realistic starting environment.

**Files/modules affected:**
- `routes/web.php` (Added resource routes and dashboard route).
- `app/Http/Controllers/CategoryController.php` (Created).
- `app/Http/Controllers/HabitController.php` (Created).
- `app/Models/User.php` (Added relationships).
- `app/Models/Category.php` (Created).
- `app/Models/Habit.php` (Created).
- `app/Models/HabitCompletion.php` (Created).
- `database/factories/*` (Created factories).
- `database/seeders/DatabaseSeeder.php` (Updated).
- `resources/views/layouts/navigation.blade.php` (Updated with new links).
- `resources/views/dashboard.blade.php` (Updated to show habits).
- `resources/views/habits/*` (Created views).
- `resources/views/categories/*` (Created views).

**Migrations created:**
- `create_categories_table`
- `create_habits_table`
- `create_habit_completions_table`

**Tests added:**
- `tests/Feature/CategoryTest.php` (Added tests for viewing and creating categories).
- `tests/Feature/HabitTest.php` (Added tests for dashboard display, creating habits, and toggling completions).

**Breaking changes:**
- None (Initial release).

---

## March 8, 2026 (Session 2)
**Features added:**
- Implemented a responsive 7-day Weekly View grid on the dashboard. Users can now see habit completions for the last 7 days and toggle statuses for any of those days.

**Files/modules affected:**
- `app/Http/Controllers/HabitController.php` (Updated to load 7 days of completions).
- `resources/views/dashboard.blade.php` (Replaced list with table grid).

**Migrations created:**
- None.

**Tests added/updated:**
- `tests/Feature/HabitTest.php` (Updated assertions for the dashboard).

**Breaking changes:**
- None.

---

## March 8, 2026 (Session 3)
**Features added:**
- Added streak calculations logic. Automatically calculates current streak and longest streak of completions.
- Updated Habits index view to display streaks with clean icons and counts, improving UI/UX feedback to users.

**Files/modules affected:**
- `app/Models/Habit.php` (Added `getStreaks` method).
- `app/Http/Controllers/HabitController.php` (Eager load `completions` relationship on index method).
- `resources/views/habits/index.blade.php` (Displayed current and longest streak).

**Migrations created:**
- None.

**Tests added/updated:**
- `tests/Feature/HabitTest.php` (Added `test_streak_calculation` to verify edge cases of dates).

**Breaking changes:**
- None.

---

## April 20, 2026
**Features added:**
- Added user timezone support.
- Users can now select and save their preferred timezone in their profile.
- Habit completions and streaks are now evaluated against the user's selected timezone rather than the server's local time, eliminating cross-timezone reporting bugs.

**Files/modules affected:**
- `app/Models/User.php` (Added `timezone` fillable).
- `app/Http/Requests/ProfileUpdateRequest.php` (Added validation logic).
- `app/Http/Controllers/HabitController.php` (Updated methods to convert "today" based on user timezone).
- `app/Models/Habit.php` (Updated `getStreaks` method to handle local dates via user timezone).
- `resources/views/dashboard.blade.php` (Re-evaluated the current day for habits using timezone context).
- `resources/views/profile/partials/update-profile-information-form.blade.php` (Added timezone selector).

**Migrations created:**
- `add_timezone_to_users_table`

**Tests added/updated:**
- `tests/Feature/ProfileTest.php` (Updated payload structure).
- `tests/Feature/HabitTimezoneTest.php` (Added explicit timezone edge-case coverage).

**Breaking changes:**
- None.
