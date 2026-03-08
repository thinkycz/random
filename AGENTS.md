# Habit Tracker - Operating Manual (AGENTS.md)

## Project Overview
This repository contains a simple, coherent Habit Tracker application built with Laravel. It is designed to help individuals build positive daily habits by allowing them to categorize tasks, mark them as complete on a daily basis, and visually track their progress over time.

## Chosen Product Concept
**Habit Tracker**: A distraction-free daily checklist for habits with a minimalist approach to streaks and categorization.

## Current Architecture Summary
- **Backend Framework:** Laravel (latest stable).
- **Database:** SQLite.
- **Authentication:** Laravel Breeze (Blade stack).
- **Frontend Styling:** Tailwind CSS.
- **Core Entities:**
  - `User` (built-in auth)
  - `Category` (groups habits, has color)
  - `Habit` (belongs to User and Category)
  - `HabitCompletion` (records a completed date for a Habit)

## Coding Conventions
- Standard Laravel directory structure and naming conventions.
- Small controllers handling HTTP requests and delegating to models.
- Eloquent relationships properly defined.
- Resource controllers for CRUD (`HabitController`, `CategoryController`).
- Basic feature tests for critical flows.
- Blade views using Tailwind utility classes for clean, responsive UI.

## Current Feature List (MVP)
- Registration, Login, Profile updates.
- Create, Read, Update, Delete (CRUD) Categories with custom colors.
- Create, Read, Update, Delete (CRUD) Habits.
- Dashboard displaying today's habits with a 1-click completion toggle.
- Database seeding with realistic fake data.

## Rules for Future Sessions
- **Never restart from scratch** unless explicitly told to do so.
- Read tracking files before making changes.
- Prioritize making manageable, coherent improvements.
- Keep the codebase runnable and tests passing.
- Maintain consistency with the minimalist Habit Tracker concept.

## Start of Session Checklist
1. Run `ls -la` and explore the repo.
2. Read `AGENTS.md` (this file).
3. Read `PROJECT_STATUS.md`.
4. Read `CHANGELOG_AUTONOMOUS.md`.
5. Read `BACKLOG.md` to select the highest-value next task.
6. Verify the current state of the app (e.g., `php artisan test`).

## End of Session Checklist
1. Ensure all new features are tested.
2. Run `php artisan test` to verify no regressions.
3. Update `PROJECT_STATUS.md`.
4. Append to `CHANGELOG_AUTONOMOUS.md`.
5. Reorganize/update `BACKLOG.md`.
6. Update `AGENTS.md` if the architecture or conventions changed.
