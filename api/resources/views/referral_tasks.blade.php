<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Referral Tasks') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-2xl font-bold">Referral Milestones</h1>
                        <a href="{{ route('create_referral_task') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-block">Create Referral Task</a>
                    </div>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2 pr-4">ID</th>
                                <th class="py-2 pr-4">Title</th>
                                <th class="py-2 pr-4">Required Referrals</th>
                                <th class="py-2 pr-4">Reward</th>
                                <th class="py-2 pr-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($referralTasks as $task)
                            <tr class="border-b">
                                <td class="py-3 pr-4">{{ $task->id }}</td>
                                <td class="py-3 pr-4 font-semibold">{{ $task->title }}</td>
                                <td class="py-3 pr-4">{{ $task->number_of_referrals }}</td>
                                <td class="py-3 pr-4">{{ number_format($task->reward) }}</td>
                                <td class="py-3 pr-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('referral_tasks.edit', $task) }}" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded">Edit</a>
                                        <form action="{{ route('referral_tasks.delete', $task) }}" method="POST" onsubmit="return confirm('Delete this referral task?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
