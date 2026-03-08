# Habit Tracker - Product Specification

## Product Vision
To provide a simple, coherent, and fast web application that helps individuals build daily habits, categorize them, and track their completions over time, fostering consistency and self-improvement.

## Target User
Individuals looking to form positive daily habits, maintain streaks, and visually track their progress in a clean and distraction-free interface.

## Core Problems Solved
- Forgetting daily tasks or habits.
- Lack of visual feedback on consistency and streaks.
- Organizing habits into meaningful areas of life (e.g., Health, Work, Personal).

## Core Entities
1. **User**: The person tracking habits. (Handled via Laravel Breeze auth).
2. **Category**: A way to group related habits together (e.g., "Fitness", "Reading").
   - Fields: `id`, `user_id`, `name`, `color`
3. **Habit**: The specific daily action to be tracked.
   - Fields: `id`, `user_id`, `category_id`, `name`, `description`
4. **HabitCompletion**: A record indicating a habit was completed on a specific date.
   - Fields: `id`, `habit_id`, `completed_date`

## MVP Scope
- User registration, login, and profile management.
- CRUD operations for Categories.
- CRUD operations for Habits.
- A central Dashboard showing habits for the current day, allowing quick 1-click completion toggling.
- Basic visual indication if a habit is done for the day.
- A seed sequence that provides a realistic starting point with categories and habits.

## Future Scope
- Weekly/monthly progress charts and streak calculations.
- Reminders and notifications.
- Habit archiving.
- Tags in addition to categories.
- Sharing habits or leaderboards with friends.
