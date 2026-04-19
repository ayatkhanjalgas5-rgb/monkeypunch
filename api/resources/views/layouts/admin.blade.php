<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - {{ config('app.name', 'MonkeyPunch') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen">
        <div class="flex">
            <aside class="hidden md:flex md:w-72 md:flex-col border-r border-slate-200 bg-white min-h-screen">
                <div class="px-6 py-5 border-b border-slate-200">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">MonkeyPunch</div>
                    <div class="mt-1 text-2xl font-bold">Admin</div>
                </div>

                <nav class="flex-1 px-4 py-5 space-y-1 text-sm">
                    <a href="{{ route('dashboard') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Dashboard</a>
                    <a href="{{ route('users') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('users*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Users</a>
                    <a href="{{ route('wallets') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('wallets') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Wallets</a>
                    <a href="{{ route('transactions') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('transactions') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Transactions</a>
                    <a href="{{ route('tasks') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('tasks*') || request()->routeIs('create_task') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Tasks</a>
                    <a href="{{ route('daily_tasks') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('daily_tasks*') || request()->routeIs('create_daily_task') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Daily Tasks</a>
                    <a href="{{ route('referral_tasks') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('referral_tasks*') || request()->routeIs('create_referral_task') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Referral Tasks</a>
                    <a href="{{ route('reward_config') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('reward_config') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Reward Config</a>
                    <a href="{{ route('withdraw_requests') }}" class="block rounded-xl px-4 py-3 {{ request()->routeIs('withdraw_requests*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Withdraw Requests</a>
                </nav>

                <div class="border-t border-slate-200 p-4">
                    <div class="rounded-2xl bg-slate-100 p-4">
                        <div class="text-xs text-slate-500">Logged in</div>
                        <div class="mt-1 font-semibold">{{ auth()->user()->name ?? 'Admin' }}</div>
                        <div class="mt-3">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="w-full rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="flex-1 min-w-0">
                <header class="border-b border-slate-200 bg-white">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold">@yield('page_title', 'Admin Panel')</h1>
                            <p class="mt-1 text-sm text-slate-500">@yield('page_subtitle', 'Manage your game')</p>
                        </div>
                    </div>
                </header>

                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    @if(session('success'))
                        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                            <div class="font-semibold">Please fix the following:</div>
                            <ul class="mt-2 list-disc pl-5 text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
