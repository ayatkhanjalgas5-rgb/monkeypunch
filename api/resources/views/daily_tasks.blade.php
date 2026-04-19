@extends('layouts.admin')

@section('title', 'Daily Tasks')
@section('page_title', 'Daily Tasks')
@section('page_subtitle', 'Manage streak rewards')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div></div>
    <a href="{{ route('create_daily_task') }}" class="rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
        Create Daily Task
    </a>
</div>

<div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">ID</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Name</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Streak</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Reward</th>
                    <th class="px-4 py-4 text-left font-semibold text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($dailyTasks as $task)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-4">{{ $task->id }}</td>
                        <td class="px-4 py-4">
                            <div class="font-medium">{{ $task->name }}</div>
                            <div class="text-xs text-slate-500">{{ $task->description }}</div>
                        </td>
                        <td class="px-4 py-4">{{ $task->required_login_streak }}</td>
                        <td class="px-4 py-4 font-semibold">{{ number_format($task->reward_coins) }}</td>
                        <td class="px-4 py-4">
                            <div class="flex gap-2">
                                <a href="{{ route('daily_tasks.edit', $task) }}" class="rounded-xl bg-amber-500 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-600">Edit</a>
                                <form method="POST" action="{{ route('daily_tasks.delete', $task) }}" onsubmit="return confirm('Delete this daily task?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-500">No daily tasks found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
