@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Quick overview of your game')

@section('content')
<div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="text-sm text-slate-500">Total Users</div>
        <div class="mt-2 text-4xl font-bold">{{ number_format($userCount) }}</div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="text-sm text-slate-500">Tasks</div>
        <div class="mt-2 text-4xl font-bold">{{ number_format($taskCount) }}</div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="text-sm text-slate-500">Daily Tasks</div>
        <div class="mt-2 text-4xl font-bold">{{ number_format($dailyTaskCount) }}</div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="text-sm text-slate-500">Pending Withdraws</div>
        <div class="mt-2 text-4xl font-bold">{{ number_format($pendingWithdrawCount) }}</div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="text-sm text-slate-500">Pending Withdraw Amount</div>
        <div class="mt-2 text-4xl font-bold">{{ number_format($pendingWithdrawAmount) }}</div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="text-sm text-slate-500">Transactions</div>
        <div class="mt-2 text-4xl font-bold">{{ number_format($transactionCount) }}</div>
    </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-semibold">Quick Links</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('users') }}" class="rounded-2xl bg-slate-100 px-4 py-4 font-medium hover:bg-slate-200">Manage Users</a>
            <a href="{{ route('tasks') }}" class="rounded-2xl bg-slate-100 px-4 py-4 font-medium hover:bg-slate-200">Manage Tasks</a>
            <a href="{{ route('daily_tasks') }}" class="rounded-2xl bg-slate-100 px-4 py-4 font-medium hover:bg-slate-200">Manage Daily Tasks</a>
            <a href="{{ route('withdraw_requests') }}" class="rounded-2xl bg-slate-100 px-4 py-4 font-medium hover:bg-slate-200">Review Withdraws</a>
        </div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-semibold">Status</h2>
        <div class="mt-4 space-y-3 text-sm text-slate-600">
            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                <span>Game Backend</span>
                <span class="font-semibold text-emerald-600">Online</span>
            </div>
            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                <span>Admin Panel</span>
                <span class="font-semibold text-emerald-600">Working</span>
            </div>
            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                <span>Withdraw Queue</span>
                <span class="font-semibold text-slate-900">{{ number_format($pendingWithdrawCount) }} pending</span>
            </div>
        </div>
    </div>
</div>
@endsection
