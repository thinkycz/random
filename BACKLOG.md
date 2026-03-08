# Backlog

Each item should have a rough priority: High, Medium, Low.

## Prioritized Next Features
- **[High] Weekly View Dashboard:** Instead of just today, show a 7-day view (e.g., Sunday-Saturday) allowing users to check off habits for previous days easily.
- **[Medium] Streak Calculation:** Calculate and display the current and longest streak for a habit on its view page.
- **[Medium] Data Visualization:** Add a simple chart to the dashboard showing completion percentages over the last 30 days.
- **[Low] Habit Archiving:** Allow users to hide a habit without deleting its history.
- **[Low] Sharing/Leaderboards:** Let users share their progress with friends.

## Bug Fixes
- **[Medium] Timezone Bug:** Currently, "today" uses the server's timezone. This could mean a habit marked at 11 PM local time is recorded as "tomorrow" if the server is ahead. A solution is needed (e.g., passing user's timezone from JS or storing it in the profile).

## UX Improvements
- **[High] Empty States:** Replace text-only empty states with visually appealing illustrations or SVGs.
- **[Medium] Drag and Drop Ordering:** Allow users to reorder habits and categories rather than sorting alphabetically or by creation date.
- **[Medium] Better Color Picker:** The native HTML5 color picker is basic. Consider a nicer, accessible color palette selection.

## Refactoring Ideas
- **[Medium] Service Classes:** If the completion logic (toggling, streaks, timezones) becomes complex, extract it into a `HabitCompletionService` rather than keeping it in the controller.
- **[Low] Component Extraction:** Extract the "Habit Row" in the dashboard and index views into a reusable Blade component.

## Testing Tasks
- **[High] Timezone Tests:** Write tests simulating users in different timezones marking a habit complete.
- **[Medium] Streak Calculation Tests:** Write tests to ensure streaks correctly reset if a day is missed, but are maintained if contiguous.
- **[Low] Front-End Testing:** Add basic browser tests (e.g., using Laravel Dusk or Pest) for the toggle button interaction.
