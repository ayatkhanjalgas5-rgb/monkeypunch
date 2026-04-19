<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Daily Task') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold mb-4">Edit Daily Reward</h1>
                    <form action="{{ route('daily_tasks.update', $dailyTask) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-sm font-bold mb-2">Name</label>
                            <input type="text" name="name" value="{{ $dailyTask->name }}" class="shadow border rounded w-full py-2 px-3" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Description</label>
                            <textarea name="description" class="shadow border rounded w-full py-2 px-3" required>{{ $dailyTask->description }}</textarea>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold mb-2">Required Login Streak</label>
                                <input type="number" name="required_login_streak" value="{{ $dailyTask->required_login_streak }}" class="shadow border rounded w-full py-2 px-3" required>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Reward Coins</label>
                                <input type="number" name="reward_coins" value="{{ $dailyTask->reward_coins }}" class="shadow border rounded w-full py-2 px-3" required>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
