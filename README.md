## About This Package

This package is not intended for commercial use. It was built for personal use and is made public in case others find it helpful.

## Installation

To add this package to your Laravel project, update your `composer.json` by adding one of the following repository configurations:

### Using Live Repository
```sh
composer config repositories.0 '{"type": "vcs", "url": "https://github.com/nickklein/habits"}'
```

### Using Local Path
For local development, use:
```sh
composer config repositories.0 '{"type": "path", "url": "../habits", "options": {"symlink": true}}'
```

### Install the Package
```sh
composer require nickklein/habits
```

## Setup

1. Run the migrations:
   ```sh
   php artisan migrate
   php artisan db:seed
   ```

2. Seed the database:
   ```sh
   php artisan run:habits-seeder
   ```

3. Execute the installation script to create a symlink for JSX files:
   ```sh
   ./install.sh
   ```

This will link the `habits` JSX files to your core Laravel project.


# Public API Routes for Habits

## Middleware
All routes in this group are protected by the `publicapi` middleware.

## Endpoints

### 1. Get Weekly Habit Notifications
**Endpoint:**
```http
GET /habit/user/{userId}/average
```
**Controller:** `PublicHabitTimeController@getWeeklyNotifications`

**Description:**
Retrieves the average habit notification time for a user on a weekly basis.

**Parameters:**
- `userId` (integer, required) - The ID of the user.

**Route Name:** `habit.time`

---

### 2. Get Daily Habit Notification
**Endpoint:**
```http
GET /habit/user/{userId}/daily
```
**Controller:** `PublicHabitTimeController@getDailyNotification`

**Description:**
Fetches the daily notification time for a user’s habit.

**Parameters:**
- `userId` (integer, required) - The ID of the user.

**Route Name:** `habit.daily`

---

### 3. Store Habit Timer Status
**Endpoint:**
```http
GET /habit/user/{userId}/habit/{habitTimeId}/timer/{status}
```
**Controller:** `PublicHabitTimeController@store`

**Description:**
Stores the status of a habit timer for a specific habit.

**Parameters:**
- `userId` (integer, required) - The ID of the user.
- `habitTimeId` (integer, required) - The ID of the habit time.
- `status` (string, required) - The status of the timer.

**Route Name:** `habit.time.store-public`

---

### 4. Check Habit Active Status
**Endpoint:**
```http
GET /habit/user/{userId}/habit/check-status
```
**Controller:** `PublicHabitTimeController@isHabitActive`

**Description:**
Checks if a user’s habit is currently active.

**Parameters:**
- `userId` (integer, required) - The ID of the user.

**Route Name:** `habit.time.check-status-public`

---

### 5. Turn Off Habit Timer
**Endpoint:**
```http
GET /habit/user/{userId}/habit/timer/off
```
**Controller:** `PublicHabitTimeController@endTimers`

**Description:**
Ends all active habit timers for a user.

**Parameters:**
- `userId` (integer, required) - The ID of the user.

**Route Name:** `habit.time.store-public`

**Note:** This route is duplicated in the provided code. Ensure there are no conflicts in the route definitions.

---
