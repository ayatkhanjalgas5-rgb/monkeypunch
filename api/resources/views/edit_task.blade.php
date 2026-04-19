<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Task') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold mb-4">Edit Task</h1>
                    <form action="{{ route('tasks.update', $task) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-sm font-bold mb-2">Name</label>
                            <input type="text" name="name" value="{{ $task->name }}" class="shadow border rounded w-full py-2 px-3" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Description</label>
                            <textarea name="description" class="shadow border rounded w-full py-2 px-3" required>{{ $task->description }}</textarea>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold mb-2">Type</label>
                                <input type="text" name="type" value="{{ $task->type }}" class="shadow border rounded w-full py-2 px-3">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Button label</label>
                                <input type="text" name="action_name" value="{{ $task->action_name }}" class="shadow border rounded w-full py-2 px-3">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Link</label>
                            <input type="text" name="link" value="{{ $task->link }}" class="shadow border rounded w-full py-2 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-2">Reward Coins</label>
                            <input type="number" name="reward_coins" value="{{ $task->reward_coins }}" class="shadow border rounded w-full py-2 px-3" required>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
