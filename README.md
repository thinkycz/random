# Habit Tracker

A simple, coherent, and fast web application built with Laravel to help individuals build daily habits, categorize them, and visually track their progress over time.

## Overview

This project is a randomly chosen but realistic product built to demonstrate a coherent MVP within an autonomous daily-run repository. It allows users to register, create categories with custom colors, define daily habits, and toggle their completions directly from a dashboard.

## Features (MVP)

- **User Authentication:** Registration, login, and profile management provided by Laravel Breeze.
- **Category Management:** Group habits into distinct areas of life (e.g., "Health", "Learning") with custom colors for visual distinction.
- **Habit Tracking:** Define specific daily actions to track.
- **Interactive Dashboard:** View all habits for the current day and toggle their completion status with a single click.
- **Realistic Seed Data:** Get started immediately with a pre-populated test user, categories, habits, and a week's worth of simulated completion history.

## Technical Stack

- **Framework:** Laravel 12.x
- **Database:** SQLite (default for easy setup)
- **Frontend Styling:** Tailwind CSS via Laravel Breeze (Blade stack)
- **Testing:** PHPUnit (Feature tests included for critical flows)

## Getting Started

Follow these instructions to set up the project locally.

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & npm

### Installation

1.  **Clone the repository (if applicable) or navigate to the project directory:**
    ```bash
    cd <project-directory>
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Install and compile frontend assets:**
    ```bash
    npm install
    npm run build
    ```

4.  **Set up the environment file:**
    ```bash
    cp .env.example .env
    ```
    *(The default `.env` is configured to use SQLite).*

5.  **Generate the application key:**
    ```bash
    php artisan key:generate
    ```

6.  **Create the SQLite database file:**
    ```bash
    touch database/database.sqlite
    ```

7.  **Run migrations and seed the database:**
    This step is crucial as it creates the necessary tables and populates the application with a test user and realistic initial data.
    ```bash
    php artisan migrate:fresh --seed
    ```

### Running the Application

Start the local development server:

```bash
php artisan serve
```

The application will be accessible at `http://localhost:8000`.

**Test User Credentials:**
If you ran the seeder, you can log in with:
- **Email:** `test@example.com`
- **Password:** `password`

## Testing

To run the feature test suite:

```bash
php artisan test
```

## Contributing / Future Runs

This repository is designed to be iteratively improved by autonomous agents. Please refer to the tracking files (`AGENTS.md`, `PROJECT_STATUS.md`, `CHANGELOG_AUTONOMOUS.md`, `BACKLOG.md`, and `PRODUCT_SPEC.md`) before making changes.
