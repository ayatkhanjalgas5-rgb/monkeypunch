<?php

namespace App\Services;

use App\Models\TelegramUser;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletService
{
    public function credit(TelegramUser $user, int|float $amount, string $type, array $meta = []): TelegramUser
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $meta) {
            $user->refresh();
            $before = (float) $user->balance;
            $user->balance = $before + $amount;
            $user->save();

            $this->log($user, $type, $amount, $before, (float) $user->balance, $meta);
            return $user;
        });
    }

    public function debit(TelegramUser $user, int|float $amount, string $type, array $meta = []): TelegramUser
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Debit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $meta) {
            $user->refresh();
            $before = (float) $user->balance;
            if ($before < $amount) {
                throw new InvalidArgumentException('Insufficient balance.');
            }

            $user->balance = $before - $amount;
            $user->save();

            $this->log($user, $type, -$amount, $before, (float) $user->balance, $meta);
            return $user;
        });
    }

    public function adjust(TelegramUser $user, int|float $amount, string $type, array $meta = []): TelegramUser
    {
        return $amount >= 0
            ? $this->credit($user, $amount, $type, $meta)
            : $this->debit($user, abs($amount), $type, $meta);
    }

    public function summary(TelegramUser $user): array
    {
        return [
            'balance' => (float) $user->balance,
            'locked_balance' => (float) $user->withdrawRequests()->whereIn('status', ['pending', 'approved'])->sum('amount'),
            'wallet_address' => $user->ton_wallet,
            'wallet_provider' => $user->wallet_provider,
        ];
    }

    public function transactionHistory(TelegramUser $user, int $limit = 50)
    {
        return $user->transactions()->limit($limit)->get([
            'id', 'type', 'amount', 'balance_before', 'balance_after', 'meta', 'created_at',
        ]);
    }

    public function withdrawSummary(TelegramUser $user, int $limit = 20): array
    {
        return [
            'items' => $user->withdrawRequests()->limit($limit)->get([
                'id', 'amount', 'wallet_address', 'network', 'status', 'tx_hash', 'admin_note', 'processed_at', 'paid_at', 'created_at',
            ]),
            'pending_total' => (float) $user->withdrawRequests()->where('status', 'pending')->sum('amount'),
            'locked_balance' => (float) $user->withdrawRequests()->whereIn('status', ['pending', 'approved'])->sum('amount'),
            'wallet_address' => $user->ton_wallet,
            'wallet_provider' => $user->wallet_provider,
        ];
    }


    private function log(TelegramUser $user, string $type, int|float $amount, float $before, float $after, array $meta = []): void
    {
        Transaction::create([
            'telegram_user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'meta' => $meta ?: null,
            'admin_user_id' => $meta['admin_user_id'] ?? null,
        ]);
    }
}
