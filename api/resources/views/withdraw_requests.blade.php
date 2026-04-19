@extends('layouts.admin')

@section('title', 'Withdraw Requests')
@section('page_title', 'Withdraw Requests')
@section('page_subtitle', 'Approve, reject, or mark requests as paid')

@section('content')
<div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">ID</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">User</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Amount</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Wallet</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Network</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($withdrawRequests as $request)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-4">{{ $request->id }}</td>
                        <td class="px-4 py-4">
                            <div class="font-medium">{{ $request->telegramUser->name ?? 'Unnamed' }}</div>
                            <div class="text-xs text-slate-500">{{ $request->telegramUser->telegram_id ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-4 font-semibold">{{ number_format($request->amount) }}</td>
                        <td class="px-4 py-4 max-w-xs truncate">{{ $request->wallet_address }}</td>
                        <td class="px-4 py-4">{{ $request->network }}</td>
                        <td class="px-4 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold
                                {{ $request->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $request->status === 'approved' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $request->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $request->status === 'rejected' ? 'bg-red-100 text-red-700' : '' }}
                            ">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('withdraw_requests.approve', $request) }}">
                                    @csrf
                                    <button class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Approve</button>
                                </form>

                                <form method="POST" action="{{ route('withdraw_requests.reject', $request) }}">
                                    @csrf
                                    <button class="rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">Reject</button>
                                </form>

                                <form method="POST" action="{{ route('withdraw_requests.paid', $request) }}">
                                    @csrf
                                    <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Mark Paid</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">No withdraw requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
