<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClickerController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\PopupController;
use App\Http\Controllers\ReferralTaskController;
use App\Http\Controllers\TelegramUserController;
use App\Http\Controllers\UserTaskController;

Route::post('/auth/telegram-user', [AuthController::class, 'telegramUser']);
Route::get('/popups', [PopupController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('referred-users', [TelegramUserController::class, 'referredUsers']);

    Route::prefix('clicker')->group(function () {
        Route::get('/sync', [ClickerController::class, 'sync']);
        Route::post('/tap', [ClickerController::class, 'tap'])->middleware('throttle:clicker-tap');
        Route::post('/buy-booster', [ClickerController::class, 'buyBooster']);
        Route::post('/buy-booster-pack', [ClickerController::class, 'buyBoosterPack']);
        Route::get('/daily-tasks', [ClickerController::class, 'listDailyTasks']);
        Route::post('/claim-daily-task', [ClickerController::class, 'claimDailyTaskReward']);
        Route::get('tasks', [UserTaskController::class, 'index']);
        Route::post('tasks/{task}', [UserTaskController::class, 'store']);
        Route::post('tasks/{task}/claim', [UserTaskController::class, 'claim']);
        Route::get('referral-tasks', [ReferralTaskController::class, 'index']);
        Route::post('referral-tasks/{task}/complete', [ReferralTaskController::class, 'complete']);
        Route::get('/leaderboard', [ClickerController::class, 'listLeaderboard']);
        Route::get('/weekly-leaderboard', [ClickerController::class, 'weeklyLeaderboard']);
        Route::post('/use-daily-booster', [ClickerController::class, 'useDailyBooster']);
        Route::post('/set-ton-wallet', [ClickerController::class, 'setTonWallet']);
        Route::post('/wallet/connect', [ClickerController::class, 'setTonWallet']);
        Route::get('/wallet', [ClickerController::class, 'walletSummary']);
        Route::get('/transactions', [ClickerController::class, 'transactions']);
        Route::get('/withdraw-requests', [ClickerController::class, 'withdrawRequests']);
        Route::post('/withdraw-requests', [ClickerController::class, 'createWithdrawRequest']);
        Route::get('/battle/current', [BattleController::class, 'current']);
        Route::post('/battle/start', [BattleController::class, 'start']);
        Route::post('/battle/punch', [BattleController::class, 'punch'])->middleware('throttle:battle-punch');
        Route::post('/battle/boost', [BattleController::class, 'boost']);
        Route::post('/battle/finish', [BattleController::class, 'finish']);
        Route::post('/battle/accept', [BattleController::class, 'accept']);

        Route::post('/test', function (Request $request) {
            $request->validate([
                'hash' => 'required|string',
                'source' => 'required|string',
                'destination' => 'required|string',
                'amount' => 'required|numeric',
                'amountInNano' => 'required|string',
            ]);

            $response = Http::get('https://testnet.toncenter.com/api/v3/transactionsByMessage', [
                'msg_hash' => $request->hash,
                'limit' => 1,
                'offset' => 0,
                'sort' => 'desc',
            ]);

            $body = $response->json();

            if (!$response->ok() || !isset($body['transactions'][0]['out_msgs'][0])) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            $outMsg = $body['transactions'][0]['out_msgs'][0];
            $isValid = $outMsg['value'] === $request->amountInNano
                && strcasecmp($outMsg['source'], $request->source) === 0
                && strcasecmp($outMsg['destination'], $request->destination) === 0;

            return [
                'source' => $outMsg['source'],
                'destination' => $outMsg['destination'],
                'value' => $outMsg['value'],
                'is_valid' => $isValid,
            ];
        });
    });

    Route::get('levels', [LevelController::class, 'index']);
});
