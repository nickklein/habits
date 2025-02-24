<?php

use Illuminate\Support\Facades\Route;
use NickKlein\Habits\Controllers\HabitInsightController;
use NickKlein\Habits\Controllers\HabitTimeController;

Route::middleware(['web', 'auth'])->group(function() {
    Route::get('/habit', [HabitInsightController::class, 'index'])->name('habits.index');
    Route::get('/habit/show/{habitId}', [HabitInsightController::class, 'show'])->name('habits.show');

    // Add/Edit/Delete Transactions
    Route::get('/habit/transactions', [HabitTimeController::class, 'transactions'])->name('habits.transactions');
    Route::get('/habit/transactions/create', [HabitTimeController::class, 'create'])->name('habits.transactions.create');
    Route::post('/habit/transactions/store', [HabitTimeController::class, 'storeHabitTimes'])->name('habits.transactions.store');
    Route::get('/habit/transactions/timer/create', [HabitTimeController::class, 'timerCreate'])->name('habits.transactions.timer.create');
    Route::post('/habit/transactions/timer/store', [HabitTimeController::class, 'timerStore'])->name('habits.transactions.timer.store');
    Route::get('/habit/transactions/timer/end', [HabitTimeController::class, 'timerEnd'])->name('habits.transactions.timer.end');
    Route::get('/habit/transactions/{habitTimesId}', [HabitTimeController::class, 'editHabitTimes'])->name('habits.transactions.edit');
    Route::get('/habit/transactions/{habitTimesId}/tags', [HabitTimeController::class, 'getTags'])->name('habits.transactions.edit.tags');
    Route::post('/habit/transactions/{habitTimesId}/tags/add', [HabitTimeController::class, 'addTag'])->name('habits.transactions.edit.add-tag');
    Route::post('/habit/transactions/{habitTimesId}/tags/destroy', [HabitTimeController::class, 'removeTag'])->name('habits.transactions.edit.remove-tag');
    Route::put('/habit/transactions/{habitTimesId}/update-times', [HabitTimeController::class, 'updateHabitTimes'])->name('habits.transactions.update');
    Route::delete('/habit/transactions/destroy/{habitTimesId}', [HabitTimeController::class, 'destroy'])->name('habits.transactions.destroy');
});
