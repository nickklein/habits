<?php

use Illuminate\Support\Facades\Route;
use NickKlein\Habits\Controllers\Public\HabitTimeController as PublicHabitTimeController;

Route::middleware(['web'])->group(function () {
    Route::group(['middleware' => 'publicapi'], function () {
        // Habits
        Route::get('/habit/user/{userId}/average', [PublicHabitTimeController::class, 'getWeeklyNotifications'])->name('habit.time');
        Route::get('/habit/user/{userId}/daily', [PublicHabitTimeController::class, 'getDailyNotification'])->name('habit.daily');
        Route::get('/habit/user/{userId}/habit/{habitTimeId}/timer/{status}', [PublicHabitTimeController::class, 'store'])->name('habit.time.store-public');

        Route::get('/habit/user/{userId}/habit/check-status', [PublicHabitTimeController::class, 'isHabitActive'])->name('habit.time.check-status-public');
        /** Deprecated. Going to remove soon **/
        /*Route::get('/habit/user/{userId}/habit/timer/off', [PublicHabitTimeController::class, 'endTimers'])->name('habit.time.store-public');*/
        /**/
        /*Route::get('/habit/user/{userId}/habit/timer/off', [PublicHabitTimeController::class, 'endTimers'])->name('habit.time.store-public');*/
    });
});
