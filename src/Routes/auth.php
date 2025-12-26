<?php

use Illuminate\Support\Facades\Route;
use NickKlein\Habits\Controllers\HabitInsightController;
use NickKlein\Habits\Controllers\HabitTimeController;
use NickKlein\Habits\Controllers\HabitController;

Route::middleware(['web', 'auth'])->group(function() {
    Route::get('/habit', [HabitController::class, 'index'])->name('habits.index');
    Route::get('/api/habits/{habitUserId}/summary', [HabitController::class, 'getHabitUserSummary'])->name('api.habits.summary');
    Route::get('/habit/insights', [HabitInsightController::class, 'index'])->name('habits.insights');
    Route::get('/api/habits/insights/{habitUserId}/summary', [HabitInsightController::class, 'getHabitUserSummary'])->name('api.habits.insights.summary');
    Route::get('/habit/show/{habitId}', [HabitInsightController::class, 'show'])->name('habits.show');
    Route::get('/habit/show/{habitId}/charts', [HabitInsightController::class, 'getChartInformation'])->name('habits.show.get-habit-information');
    Route::get('/habits/show/yearly-comparison/{habitId}', [HabitInsightController::class, 'getYearlyComparisonChartForHabit'])->name('habits.yearly-comparison.habit');
    
    // Create new habits
    Route::get('/habit/create', [HabitController::class, 'create'])->name('habits.create');
    Route::post('/habit/store', [HabitController::class, 'store'])->name('habits.store');
    
    // API endpoints for AJAX calls

    // Add/Edit/Delete Transactions
    Route::get('/habit/transactions', [HabitTimeController::class, 'transactions'])->name('habits.transactions');
    Route::get('/habit/transactions/create', [HabitTimeController::class, 'create'])->name('habits.transactions.create');
    Route::post('/habit/transactions/store', [HabitTimeController::class, 'storeHabitTransaction'])->name('habits.transactions.store');
    Route::get('/habit/transactions/timer/create', [HabitTimeController::class, 'timerCreate'])->name('habits.transactions.timer.create');
    Route::post('/habit/transactions/timer/store', [HabitTimeController::class, 'timerStore'])->name('habits.transactions.timer.store');
    Route::get('/habit/transactions/timer/end', [HabitTimeController::class, 'timerEnd'])->name('habits.transactions.timer.end');
    Route::get('/habit/transactions/{habitTimesId}', [HabitTimeController::class, 'editHabitTransaction'])->name('habits.transactions.edit');
    Route::get('/habit/transactions/{habitTimesId}/tags', [HabitTimeController::class, 'getTags'])->name('habits.transactions.edit.tags');
    Route::post('/habit/transactions/{habitTimesId}/tags/add', [HabitTimeController::class, 'addTag'])->name('habits.transactions.edit.add-tag');
    Route::post('/habit/transactions/{habitTimesId}/tags/destroy', [HabitTimeController::class, 'removeTag'])->name('habits.transactions.edit.remove-tag');
    Route::put('/habit/transactions/{habitTimesId}/update-transaction', [HabitTimeController::class, 'updateHabitTransaction'])->name('habits.transactions.update');
    Route::delete('/habit/transactions/destroy/{habitTimesId}', [HabitTimeController::class, 'destroy'])->name('habits.transactions.destroy');
});
