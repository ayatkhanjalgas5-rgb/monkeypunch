<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Reward Config') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold mb-4">Reward Config</h1>
                    <p class="text-sm text-gray-500 mb-6">Use the edit buttons to change reward values for social tasks, daily rewards and referral milestones.</p>

                    <div class="grid lg:grid-cols-3 gap-6">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-lg font-semibold">Social Tasks</h2>
                                <a href="{{ route('tasks') }}" class="text-blue-600">Manage</a>
                            </div>
                            <div class="space-y-2">
                                @foreach($tasks as $task)
                                <div class="border rounded p-3 flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-medium">{{ $task->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $task->type ?: 'other' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold">{{ number_format($task->reward_coins) }}</div>
                                        <a href="{{ route('tasks.edit', $task) }}" class="text-xs text-blue-600">Edit</a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-lg font-semibold">Daily Rewards</h2>
                                <a href="{{ route('daily_tasks') }}" class="text-blue-600">Manage</a>
                            </div>
                            <div class="space-y-2">
                                @foreach($dailyTasks as $task)
                                <div class="border rounded p-3 flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-medium">{{ $task->name }}</div>
                                        <div class="text-xs text-gray-500">Streak {{ $task->required_login_streak }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold">{{ number_format($task->reward_coins) }}</div>
                                        <a href="{{ route('daily_tasks.edit', $task) }}" class="text-xs text-blue-600">Edit</a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-lg font-semibold">Referral Milestones</h2>
                                <a href="{{ route('referral_tasks') }}" class="text-blue-600">Manage</a>
                            </div>
                            <div class="space-y-2">
                                @foreach($referralTasks as $task)
                                <div class="border rounded p-3 flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-medium">{{ $task->title }}</div>
                                        <div class="text-xs text-gray-500">{{ $task->number_of_referrals }} referrals</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold">{{ number_format($task->reward) }}</div>
                                        <a href="{{ route('referral_tasks.edit', $task) }}" class="text-xs text-blue-600">Edit</a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
