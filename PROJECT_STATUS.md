# Project Status

## Current Date/Session Summary
**Date:** March 9, 2026

**Summary:** Added a weekly view to the dashboard, allowing users to toggle their habits for the past 7 days instead of just today. Upgraded the test suite to use the main database rather than in-memory SQLite to fix persistent environment issues.

## What has been completed
- Installed Laravel Breeze and configured authentication (Blade/Tailwind).
- Defined the product concept as a Habit Tracker.
- Created database models, migrations, factories, and seeders for Categories, Habits, and Habit Completions.
- Built CRUD interfaces for Habits and Categories.
- Built a weekly dashboard to view and toggle habit completion for the last 7 days.
- Adjusted tests to reflect the updated UI and support real database connections.
- Documented changes to the tracking files.

## What is in progress
- None.

## Known Issues
- The dashboard only lets users look back 7 days without an option to go further back.
- Users might accidentally complete habits on incorrect dates if the timezone differs between server and client. (Currently server-side timestamp is used).
- If a category is deleted, habits associated with it lose the category but still remain. This behavior is intentional for now but needs clearer UI messaging in the future.

## Next Recommended Tasks
- **Pagination for Past Dates:** Allow users to scroll further into the past on the weekly view.
- **Streaks:** Calculate and display current and longest streaks for each habit.
- **Client-Side Timezone Handling:** Ensure "today" aligns with the user's local timezone.
- **Refactoring:** Extract completion logic into a dedicated Service or Action class if it grows more complex.

## Technical Debt Notes
- The "toggle" logic in `HabitController` is directly manipulating completions and dates based on server time. This works for MVP but could lead to bugs if the user is in a timezone where "today" differs from UTC.
- UI empty states are basic text; they could benefit from simple illustrations or icons.

## Test Coverage Notes
- Basic feature tests cover:
  - Viewing categories.
  - Creating categories.
  - Viewing habits on the dashboard.
  - Creating habits.
  - Toggling habit completions.
  - Asserting users cannot view/edit others' data (authorization).
- Test coverage is moderate. Edge cases, like timezone issues, are not yet tested.

## Setup or Environment Notes
- **Database:** SQLite is used locally.
- **Frontend:** Requires Node.js (`npm install && npm run build`).
- Ensure `php artisan migrate:fresh --seed` is run to populate realistic fake data.
