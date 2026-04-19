<?php

namespace App\Http\Controllers;

use App\Models\DailyTask;
use App\Models\ReferralTask;
use App\Models\Task;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\WithdrawRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct(private WalletService $walletService)
    {
    }

    public function dashboard()
    {
        $userCount = TelegramUser::count();
        $taskCount = Task::count();
        $dailyTaskCount = DailyTask::count();
        $pendingWithdrawCount = WithdrawRequest::where('status', 'pending')->count();
        $pendingWithdrawAmount = WithdrawRequest::where('status', 'pending')->sum('amount');
        $transactionCount = Transaction::count();

        return view('dashboard', compact('userCount', 'taskCount', 'dailyTaskCount', 'pendingWithdrawCount', 'pendingWithdrawAmount', 'transactionCount'));
    }

    public function users()
    {
        return view('users', [
            'users' => TelegramUser::withCount('withdrawRequests')->latest()->get(),
        ]);
    }

    public function wallets()
    {
        return view('wallets', [
            'users' => TelegramUser::orderByDesc('balance')->get(),
        ]);
    }

    public function updateUserBalance(Request $request, TelegramUser $telegramUser)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'reason' => 'nullable|string|max:255',
        ]);

        $this->walletService->adjust($telegramUser, (float) $validated['amount'], 'admin', [
            'reason' => $validated['reason'] ?? 'manual_adjustment',
            'admin_user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Balance updated successfully.');
    }

    public function transactions()
    {
        return view('transactions', [
            'transactions' => Transaction::with(['telegramUser', 'adminUser'])->latest()->limit(500)->get(),
        ]);
    }

    public function tasks()
    {
        return view('tasks', ['tasks' => Task::latest()->get()]);
    }

    public function createTask()
    {
        return view('create_task');
    }

    public function storeTask(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reward_coins' => 'required|integer|min:1',
            'type' => 'nullable|string|max:50',
            'link' => 'nullable|string|max:1000',
            'action_name' => 'nullable|string|max:100',
        ]);

        Task::create($validated);

        return redirect()->route('tasks')->with('success', 'Task created successfully');
    }

    public function editTask(Task $task)
    {
        return view('edit_task', compact('task'));
    }

    public function updateTask(Request $request, Task $task)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reward_coins' => 'required|integer|min:1',
            'type' => 'nullable|string|max:50',
            'link' => 'nullable|string|max:1000',
            'action_name' => 'nullable|string|max:100',
        ]);

        $task->update($validated);

        return redirect()->route('tasks')->with('success', 'Task updated successfully');
    }

    public function deleteTask(Task $task)
    {
        $task->delete();
        return redirect()->route('tasks')->with('success', 'Task deleted successfully');
    }

    public function dailyTasks()
    {
        return view('daily_tasks', ['dailyTasks' => DailyTask::orderBy('required_login_streak')->get()]);
    }

    public function createDailyTask()
    {
        return view('create_daily_task');
    }

    public function storeDailyTask(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'required_login_streak' => 'required|integer|min:1|max:365',
            'reward_coins' => 'required|integer|min:1',
        ]);

        DailyTask::create($validated);

        return redirect()->route('daily_tasks')->with('success', 'Daily task created successfully');
    }

    public function editDailyTask(DailyTask $dailyTask)
    {
        return view('edit_daily_task', compact('dailyTask'));
    }

    public function updateDailyTask(Request $request, DailyTask $dailyTask)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'required_login_streak' => 'required|integer|min:1|max:365',
            'reward_coins' => 'required|integer|min:1',
        ]);

        $dailyTask->update($validated);

        return redirect()->route('daily_tasks')->with('success', 'Daily task updated successfully');
    }

    public function deleteDailyTask(DailyTask $dailyTask)
    {
        $dailyTask->delete();
        return redirect()->route('daily_tasks')->with('success', 'Daily task deleted successfully');
    }

    public function referralTasks()
    {
        return view('referral_tasks', ['referralTasks' => ReferralTask::orderBy('number_of_referrals')->get()]);
    }

    public function createReferralTask()
    {
        return view('create_referral_task');
    }

    public function storeReferralTask(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'number_of_referrals' => 'required|integer|min:1|max:255',
            'reward' => 'required|integer|min:1',
        ]);

        ReferralTask::create($validated);

        return redirect()->route('referral_tasks')->with('success', 'Referral task created successfully');
    }

    public function editReferralTask(ReferralTask $referralTask)
    {
        return view('edit_referral_task', compact('referralTask'));
    }

    public function updateReferralTask(Request $request, ReferralTask $referralTask)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'number_of_referrals' => 'required|integer|min:1|max:255',
            'reward' => 'required|integer|min:1',
        ]);

        $referralTask->update($validated);

        return redirect()->route('referral_tasks')->with('success', 'Referral task updated successfully');
    }

    public function deleteReferralTask(ReferralTask $referralTask)
    {
        $referralTask->delete();
        return redirect()->route('referral_tasks')->with('success', 'Referral task deleted successfully');
    }

    public function rewardConfig()
    {
        return view('reward_config', [
            'tasks' => Task::orderBy('reward_coins', 'desc')->get(),
            'dailyTasks' => DailyTask::orderBy('required_login_streak')->get(),
            'referralTasks' => ReferralTask::orderBy('number_of_referrals')->get(),
        ]);
    }

    public function withdrawRequests()
    {
        $withdrawRequests = WithdrawRequest::with(['telegramUser', 'processedBy'])->latest()->get();
        return view('withdraw_requests', compact('withdrawRequests'));
    }

    public function approveWithdrawRequest(Request $request, WithdrawRequest $withdrawRequest)
    {
        if ($withdrawRequest->status !== 'pending') {
            return redirect()->route('withdraw_requests')->with('success', 'Withdraw request already processed.');
        }

        $withdrawRequest->update([
            'status' => 'approved',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'admin_note' => $request->input('admin_note'),
        ]);

        return redirect()->route('withdraw_requests')->with('success', 'Withdraw request approved.');
    }

public function rejectWithdrawRequest(Request $request, WithdrawRequest $withdrawRequest)
{
    try {
        DB::transaction(function () use ($request, $withdrawRequest) {

            // 🔥 Lock row
            $withdrawRequest = WithdrawRequest::where('id', $withdrawRequest->id)
                ->lockForUpdate()
                ->first();

            if ($withdrawRequest->status !== 'pending') {
                throw new \Exception('Already processed');
            }

            $this->walletService->credit(
                $withdrawRequest->telegramUser,
                $withdrawRequest->amount,
                'withdraw',
                [
                    'reason' => 'withdraw_rejected_refund',
                    'withdraw_request_id' => $withdrawRequest->id,
                    'admin_user_id' => auth()->id(),
                ]
            );

            $withdrawRequest->update([
                'status' => 'rejected',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
                'admin_note' => $request->input('admin_note'),
            ]);
        });

    } catch (\Throwable $e) {
        return redirect()->route('withdraw_requests')->with('error', 'Failed to reject withdraw.');
    }

    return redirect()->route('withdraw_requests')->with('success', 'Withdraw request rejected and balance refunded.');
}

    public function markWithdrawRequestPaid(Request $request, WithdrawRequest $withdrawRequest)
    {
        if (!in_array($withdrawRequest->status, ['approved', 'paid'], true)) {
            return redirect()->route('withdraw_requests')->with('success', 'Withdraw request must be approved before marking as paid.');
        }

        $validated = $request->validate([
            'tx_hash' => 'required|string|max:255',
            'admin_note' => 'nullable|string',
        ]);

        $withdrawRequest->update([
            'status' => 'paid',
            'tx_hash' => $validated['tx_hash'],
            'paid_at' => now(),
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'admin_note' => $validated['admin_note'] ?? $request->input('admin_note'),
        ]);

        return redirect()->route('withdraw_requests')->with('success', 'Withdraw request marked as paid.');
    }
}
