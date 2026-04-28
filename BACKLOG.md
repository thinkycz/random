# Backlog

Each item should have a rough priority: High, Medium, Low.

## Prioritized Next Features
- **[Medium] Data Visualization:** Add a simple chart to the dashboard showing completion percentages over the last 30 days.
- **[Low] Habit Archiving:** Allow users to hide a habit without deleting its history.
- **[Low] Sharing/Leaderboards:** Let users share their progress with friends.

## Bug Fixes
- None currently reported.

## UX Improvements
- **[High] Empty States:** Replace text-only empty states with visually appealing illustrations or SVGs.
- **[Medium] Drag and Drop Ordering:** Allow users to reorder habits and categories rather than sorting alphabetically or by creation date.
- **[Medium] Better Color Picker:** The native HTML5 color picker is basic. Consider a nicer, accessible color palette selection.

## Refactoring Ideas
- **[Medium] Service Classes:** If the completion logic (toggling, streaks, timezones) becomes complex, extract it into a `HabitCompletionService` rather than keeping it in the controller.
- **[Low] Component Extraction:** Extract the "Habit Row" in the dashboard and index views into a reusable Blade component.

## Testing Tasks
- **[High] Timezone Tests:** Write tests simulating users in different timezones marking a habit complete.
- **[Low] Front-End Testing:** Add basic browser tests (e.g., using Laravel Dusk or Pest) for the toggle button interaction.
