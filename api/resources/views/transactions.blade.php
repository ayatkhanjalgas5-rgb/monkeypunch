@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Transactions</h1>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="p-3 text-left">Time</th>
                    <th class="p-3 text-left">User</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Before</th>
                    <th class="p-3 text-left">After</th>
                    <th class="p-3 text-left">Meta</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $item)
                    <tr class="border-t align-top">
                        <td class="p-3 text-xs text-gray-600">{{ $item->created_at }}</td>
                        <td class="p-3">
                            <div class="font-semibold">{{ $item->telegramUser?->first_name }} {{ $item->telegramUser?->last_name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->telegramUser?->telegram_id }}</div>
                        </td>
                        <td class="p-3 uppercase">{{ $item->type }}</td>
                        <td class="p-3 font-semibold {{ $item->amount >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($item->amount, 2) }}</td>
                        <td class="p-3">{{ number_format($item->balance_before, 2) }}</td>
                        <td class="p-3">{{ number_format($item->balance_after, 2) }}</td>
                        <td class="p-3 text-xs text-gray-600"><pre class="whitespace-pre-wrap">{{ json_encode($item->meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-4 text-center text-gray-500">No transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
