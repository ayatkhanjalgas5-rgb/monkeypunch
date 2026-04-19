<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\WalletService;

class UserTaskController extends Controller
{
    public function __construct(private WalletService $walletService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = Task::query()
            ->leftJoin('telegram_user_tasks', function ($join) use ($user) {
                $join->on('tasks.id', '=', 'telegram_user_tasks.task_id')
                    ->where('telegram_user_tasks.telegram_user_id', $user->id);
            })
            ->select(['tasks.*', 'telegram_user_tasks.is_submitted', 'telegram_user_tasks.is_rewarded', 'telegram_user_tasks.submitted_at'])
            ->orderByDesc('tasks.id')
            ->get()
            ->map(function ($task) {
                return array_merge($task->toArray(), [
                    'submitted_at' => $task->submitted_at ? Carbon::parse($task->submitted_at)->toIso8601String() : null,
                ]);
            });

        return response()->json($tasks);
    }

    public function store(Request $request, Task $task)
    {
        $user = $request->user();

        $userTask = $user->tasks()->where('task_id', $task->id)->first();

        if ($userTask) {
            if ($userTask->pivot->is_rewarded) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task already rewarded.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task already opened. Finish it, wait a moment, then claim your reward.',
            ]);
        }

        $user->tasks()->attach($task->id, ['is_submitted' => true, 'submitted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Task opened successfully. Finish it, wait a moment, then come back to claim your reward.',
        ]);
    }

    public function claim(Request $request, Task $task)
    {
        $user = $request->user();

        $userTask = $user->tasks()->where('task_id', $task->id)->first();

        if (!$userTask) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        if (!$userTask->pivot->is_submitted || !$userTask->pivot->submitted_at) {
            return response()->json(['success' => false, 'message' => 'Open the task first.'], 400);
        }

        if ($userTask->pivot->is_rewarded) {
            return response()->json(['success' => false, 'message' => 'Task already rewarded.'], 400);
        }

        $cooldownSeconds = max(config('admin.task_claim_cooldown_seconds', 15), 0);
        $claimAvailableAt = Carbon::parse($userTask->pivot->submitted_at)->addSeconds($cooldownSeconds);

        if ($cooldownSeconds > 0 && now()->lt($claimAvailableAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Please spend a little time on the task before claiming the reward.',
                'claim_available_at' => $claimAvailableAt->toIso8601String(),
            ], 422);
        }

        $claimed = false;
        DB::transaction(function () use ($task, &$claimed, $user, $userTask) {
            $userTask->pivot->is_rewarded = true;
            $userTask->pivot->save();

            $this->walletService->credit($user, $task->reward_coins, 'earn', ['task_id' => $task->id, 'task_name' => $task->name]);

            $claimed = true;
        });

        if (!$claimed) {
            return response()->json(['success' => false, 'message' => 'Unable to claim reward.'], 400);
        }

        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => "You have successfully claimed $task->reward_coins from $task->name.",
            'balance' => $user->balance,
        ]);
    }
}
