<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Create Task') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold mb-4">Create Social Task</h1>
                    <form action="{{ route('store_task') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="name" class="block text-sm font-bold mb-2">Name</label>
                            <input type="text" name="name" id="name" class="shadow border rounded w-full py-2 px-3" required>
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-bold mb-2">Description</label>
                            <textarea name="description" id="description" class="shadow border rounded w-full py-2 px-3" required></textarea>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label for="type" class="block text-sm font-bold mb-2">Type</label>
                                <select name="type" id="type" class="shadow border rounded w-full py-2 px-3">
                                    <option value="other">Other</option>
                                    <option value="twitter">X / Twitter</option>
                                    <option value="telegram">Telegram</option>
                                    <option value="video">YouTube / Video</option>
                                </select>
                            </div>
                            <div>
                                <label for="action_name" class="block text-sm font-bold mb-2">Button label</label>
                                <input type="text" name="action_name" id="action_name" value="Open" class="shadow border rounded w-full py-2 px-3">
                            </div>
                        </div>
                        <div>
                            <label for="link" class="block text-sm font-bold mb-2">Link</label>
                            <input type="text" name="link" id="link" class="shadow border rounded w-full py-2 px-3" placeholder="https://x.com/...">
                        </div>
                        <div>
                            <label for="reward_coins" class="block text-sm font-bold mb-2">Reward Coins</label>
                            <input type="number" name="reward_coins" id="reward_coins" class="shadow border rounded w-full py-2 px-3" required>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Create Task</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
