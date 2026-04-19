<?php

namespace App\Http\Controllers;

use App\Models\ReferralTask;
use App\Services\WalletService;
use Illuminate\Http\Request;

class ReferralTaskController extends Controller
{
    public function __construct(private WalletService $walletService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = ReferralTask::query()
            ->leftJoin('telegram_user_referral_task', function ($join) use ($user) {
                $join->on('referral_tasks.id', '=', 'telegram_user_referral_task.referral_task_id')
                    ->where('telegram_user_referral_task.telegram_user_id', $user->id);
            })
            ->select(['referral_tasks.*', 'telegram_user_referral_task.is_completed'])
            ->get();

        return response()->json($tasks);
    }

    public function complete(Request $request, ReferralTask $task)
    {
        $user = $request->user();
        $totalReferrals = $user->referrals()->count();

        if ($user->referralTasks->contains($task)) {
            return response()->json(['success' => false, 'message' => 'Task already completed.'], 400);
        }

        if ($totalReferrals < $task->number_of_referrals) {
            return response()->json(['success' => false, 'message' => 'Not enough referrals.'], 400);
        }

        $user->referralTasks()->attach($task->id, ['is_completed' => true]);

        $this->walletService->credit($user, $task->reward, 'referral', ['referral_task_id' => $task->id, 'title' => $task->title]);

        return response()->json([
            'success' => true,
            'message' => 'Task completed'
        ]);
    }
}
