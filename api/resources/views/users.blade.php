@extends('layouts.admin')

@section('title', 'Users')
@section('page_title', 'Users')
@section('page_subtitle', 'Manage player balances and view activity')

@section('content')
<div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">ID</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Name</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Telegram ID</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Balance</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Withdraws</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Adjust Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-4">{{ $user->id }}</td>
                        <td class="px-4 py-4 font-medium">{{ $user->name ?? 'Unnamed' }}</td>
                        <td class="px-4 py-4">{{ $user->telegram_id }}</td>
                        <td class="px-4 py-4 font-semibold">{{ number_format($user->balance ?? 0) }}</td>
                        <td class="px-4 py-4">{{ $user->withdraw_requests_count }}</td>
                        <td class="px-4 py-4">
                            <form method="POST" action="{{ route('users.balance.update', $user) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <input
                                    type="number"
                                    step="1"
                                    name="amount"
                                    placeholder="+100 or -100"
                                    class="w-32 rounded-xl border border-slate-300 px-3 py-2"
                                    required
                                >
                                <input
                                    type="text"
                                    name="reason"
                                    placeholder="Reason"
                                    class="w-40 rounded-xl border border-slate-300 px-3 py-2"
                                >
                                <button class="rounded-xl bg-slate-900 px-4 py-2 font-semibold text-white hover:bg-slate-800">
                                    Update
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
