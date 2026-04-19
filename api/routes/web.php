<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/wallets', [AdminController::class, 'wallets'])->name('wallets');
    Route::post('/users/{telegramUser}/balance', [AdminController::class, 'updateUserBalance'])->name('users.balance.update');
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');

    Route::get('/tasks', [AdminController::class, 'tasks'])->name('tasks');
    Route::get('/tasks/create', [AdminController::class, 'createTask'])->name('create_task');
    Route::post('/tasks', [AdminController::class, 'storeTask'])->name('store_task');
    Route::get('/tasks/{task}/edit', [AdminController::class, 'editTask'])->name('tasks.edit');
    Route::put('/tasks/{task}', [AdminController::class, 'updateTask'])->name('tasks.update');
    Route::delete('/tasks/{task}', [AdminController::class, 'deleteTask'])->name('tasks.delete');

    Route::get('/daily-tasks', [AdminController::class, 'dailyTasks'])->name('daily_tasks');
    Route::get('/daily-tasks/create', [AdminController::class, 'createDailyTask'])->name('create_daily_task');
    Route::post('/daily-tasks', [AdminController::class, 'storeDailyTask'])->name('store_daily_task');
    Route::get('/daily-tasks/{dailyTask}/edit', [AdminController::class, 'editDailyTask'])->name('daily_tasks.edit');
    Route::put('/daily-tasks/{dailyTask}', [AdminController::class, 'updateDailyTask'])->name('daily_tasks.update');
    Route::delete('/daily-tasks/{dailyTask}', [AdminController::class, 'deleteDailyTask'])->name('daily_tasks.delete');

    Route::get('/referral-tasks', [AdminController::class, 'referralTasks'])->name('referral_tasks');
    Route::get('/referral-tasks/create', [AdminController::class, 'createReferralTask'])->name('create_referral_task');
    Route::post('/referral-tasks', [AdminController::class, 'storeReferralTask'])->name('store_referral_task');
    Route::get('/referral-tasks/{referralTask}/edit', [AdminController::class, 'editReferralTask'])->name('referral_tasks.edit');
    Route::put('/referral-tasks/{referralTask}', [AdminController::class, 'updateReferralTask'])->name('referral_tasks.update');
    Route::delete('/referral-tasks/{referralTask}', [AdminController::class, 'deleteReferralTask'])->name('referral_tasks.delete');

    Route::get('/reward-config', [AdminController::class, 'rewardConfig'])->name('reward_config');

    Route::get('/withdraw-requests', [AdminController::class, 'withdrawRequests'])->name('withdraw_requests');
    Route::post('/withdraw-requests/{withdrawRequest}/approve', [AdminController::class, 'approveWithdrawRequest'])->name('withdraw_requests.approve');
    Route::post('/withdraw-requests/{withdrawRequest}/reject', [AdminController::class, 'rejectWithdrawRequest'])->name('withdraw_requests.reject');
    Route::post('/withdraw-requests/{withdrawRequest}/paid', [AdminController::class, 'markWithdrawRequestPaid'])->name('withdraw_requests.paid');
});

require __DIR__.'/auth.php';
