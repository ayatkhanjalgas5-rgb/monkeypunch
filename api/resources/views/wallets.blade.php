@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Wallets</h1>
        @if (session('success'))
            <div class="text-sm text-green-600">{{ session('success') }}</div>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="p-3 text-left">User</th>
                    <th class="p-3 text-left">Balance</th>
                    <th class="p-3 text-left">Locked</th>
                    <th class="p-3 text-left">Wallet</th>
                    <th class="p-3 text-left">Adjust balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-t align-top">
                        <td class="p-3">
                            <div class="font-semibold">{{ $user->first_name }} {{ $user->last_name }}</div>
                            <div class="text-xs text-gray-500">ID: {{ $user->telegram_id }}</div>
                            <div class="text-xs text-gray-500">@{{ $user->username ?: 'no_username' }}</div>
                        </td>
                        <td class="p-3 font-semibold">{{ number_format($user->balance, 2) }}</td>
                        <td class="p-3">{{ number_format($user->withdrawRequests()->whereIn('status', ['pending','approved'])->sum('amount'), 2) }}</td>
                        <td class="p-3 break-all max-w-xs">{{ $user->ton_wallet ?: '—' }}</td>
                        <td class="p-3 min-w-[320px]">
                            <form method="POST" action="{{ route('users.balance.update', $user) }}" class="grid gap-2 md:grid-cols-[1fr_2fr_auto]">
                                @csrf
                                <input name="amount" type="number" step="0.01" placeholder="Use +100 or -100" class="rounded border px-3 py-2" required />
                                <input name="reason" placeholder="Reason" class="rounded border px-3 py-2" />
                                <button class="rounded bg-indigo-600 px-4 py-2 text-white">Save</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
