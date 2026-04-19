<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Referral Task') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold mb-4">Edit Referral Milestone</h1>
                    <form action="{{ route('referral_tasks.update', $referralTask) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-sm font-bold mb-2">Title</label>
                            <input type="text" name="title" value="{{ $referralTask->title }}" class="shadow border rounded w-full py-2 px-3" required>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold mb-2">Required Referrals</label>
                                <input type="number" name="number_of_referrals" value="{{ $referralTask->number_of_referrals }}" class="shadow border rounded w-full py-2 px-3" required>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Reward</label>
                                <input type="number" name="reward" value="{{ $referralTask->reward }}" class="shadow border rounded w-full py-2 px-3" required>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
