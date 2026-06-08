<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} — ServiceBoard</title>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-zinc-950 text-zinc-100 font-sans antialiased"
      x-data="{ sidebarOpen: $persist(true), mobileOpen: false }">

<div class="flex h-full">

    {{-- Mobile backdrop --}}
    <div x-show="mobileOpen"
         x-cloak
         class="fixed inset-0 z-40 bg-zinc-950/80 backdrop-blur-sm lg:hidden"
         @click="mobileOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Sidebar                                                             --}}
    {{-- ------------------------------------------------------------------ --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex flex-col bg-zinc-900 border-r border-zinc-800/80 transition-all duration-300
                  w-64 -translate-x-full lg:translate-x-0"
           :class="{
               'translate-x-0': mobileOpen,
               'lg:w-60': sidebarOpen,
               'lg:w-16': !sidebarOpen,
           }">

        {{-- Logo --}}
        <div class="flex h-16 shrink-0 items-center gap-3 px-4 border-b border-zinc-800/80">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 shadow-[0_0_12px_theme(colors.blue.600/40%)]">
                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </div>
            <div x-show="sidebarOpen || mobileOpen" x-transition.opacity class="min-w-0 flex-1">
                <p class="text-sm font-bold text-white leading-none">ServiceBoard</p>
                <p class="text-xs text-zinc-500 mt-0.5">Infrastructure Dashboard</p>
            </div>
            {{-- Close button (mobile only) --}}
            <button @click="mobileOpen = false"
                    x-show="mobileOpen"
                    x-cloak
                    class="lg:hidden ml-auto flex items-center justify-center h-7 w-7 rounded-md text-zinc-600 hover:text-zinc-300 hover:bg-zinc-800 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Workspace Switcher --}}
        <div class="border-b border-zinc-800/80 px-3 py-2" x-data="{ wsOpen: false }">

            {{-- Expanded --}}
            <div x-show="sidebarOpen || mobileOpen" x-transition.opacity class="relative">
                <button @click="wsOpen = !wsOpen"
                        class="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800 transition-colors">
                    <span class="text-base leading-none shrink-0">{{ $activeWorkspace->type->icon() }}</span>
                    <span class="flex-1 truncate text-left">{{ $activeWorkspace->name }}</span>
                    <svg class="h-3.5 w-3.5 text-zinc-600 shrink-0 transition-transform duration-200"
                         :class="wsOpen ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div x-show="wsOpen"
                     x-cloak
                     @click.outside="wsOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute top-full left-0 right-0 mt-1.5 rounded-xl border border-zinc-700/60 bg-zinc-900 shadow-xl overflow-hidden z-50">
                    @auth
                        @foreach(auth()->user()->workspaces as $ws)
                            <form method="POST" action="{{ route('workspace.switch', $ws) }}">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-2.5 px-3 py-2.5 text-sm transition-colors
                                               {{ $ws->id === $activeWorkspace->id ? 'bg-zinc-800/80 text-zinc-100' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }}">
                                    <span class="text-base leading-none shrink-0">{{ $ws->type->icon() }}</span>
                                    <span class="flex-1 truncate text-left">{{ $ws->name }}</span>
                                    @if($ws->id === $activeWorkspace->id)
                                        <svg class="h-3.5 w-3.5 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    @endauth
                </div>
            </div>

            {{-- Collapsed (icon only) --}}
            <div x-show="!sidebarOpen && !mobileOpen" class="flex justify-center py-0.5">
                <span class="text-lg leading-none" title="{{ $activeWorkspace->name }}">{{ $activeWorkspace->type->icon() }}</span>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto py-4 space-y-5">

            {{-- Monitoring --}}
            <div class="px-3">
                <p x-show="sidebarOpen || mobileOpen" x-transition.opacity
                   class="mb-1.5 px-2 text-xs font-semibold uppercase tracking-widest text-zinc-600">
                    Monitoring
                </p>
                @php
                    $monitoring = [
                        ['route' => 'dashboard',     'label' => 'Übersicht',  'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                        ['route' => 'servers.index', 'label' => 'Server',     'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01'],
                        ['route' => 'docker.index',  'label' => 'Container',  'icon' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
                    ];
                @endphp
                @foreach ($monitoring as $item)
                    @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       @click="mobileOpen = false"
                       class="group flex items-center gap-3 rounded-lg px-2 py-2 text-sm font-medium transition-all
                              {{ $active ? 'bg-blue-600/15 text-blue-400 ring-1 ring-blue-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }}">
                        <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-blue-400' : 'text-zinc-500 group-hover:text-zinc-300' }}"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span x-show="sidebarOpen || mobileOpen" x-transition.opacity class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Divider --}}
            <div class="px-3">
                <div class="border-t border-zinc-800/60"></div>
            </div>

            {{-- Cloud --}}
            <div class="px-3">
                <p x-show="sidebarOpen || mobileOpen" x-transition.opacity
                   class="mb-1.5 px-2 text-xs font-semibold uppercase tracking-widest text-zinc-600">
                    Cloud
                </p>
                @php
                    $cloud = [
                        ['route' => 'cloudflare.index', 'label' => 'Domains', 'icon' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253'],
                        ['route' => 'cloudflare.dns',   'label' => 'DNS',     'icon' => 'M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z'],
                    ];
                @endphp
                @foreach ($cloud as $item)
                    @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       @click="mobileOpen = false"
                       class="group flex items-center gap-3 rounded-lg px-2 py-2 text-sm font-medium transition-all
                              {{ $active ? 'bg-orange-600/15 text-orange-400 ring-1 ring-orange-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }}">
                        <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-orange-400' : 'text-zinc-500 group-hover:text-zinc-300' }}"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span x-show="sidebarOpen || mobileOpen" x-transition.opacity class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Divider --}}
            <div class="px-3">
                <div class="border-t border-zinc-800/60"></div>
            </div>

            {{-- Projekt --}}
            <div class="px-3">
                <p x-show="sidebarOpen || mobileOpen" x-transition.opacity
                   class="mb-1.5 px-2 text-xs font-semibold uppercase tracking-widest text-zinc-600">
                    Projekt
                </p>
                @php $workflowActive = request()->routeIs('workflow.*'); @endphp
                <a href="{{ route('workflow.index') }}"
                   @click="mobileOpen = false"
                   class="group flex items-center gap-3 rounded-lg px-2 py-2 text-sm font-medium transition-all
                          {{ $workflowActive ? 'bg-indigo-600/15 text-indigo-400 ring-1 ring-indigo-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }}">
                    <svg class="h-[18px] w-[18px] shrink-0 {{ $workflowActive ? 'text-indigo-400' : 'text-zinc-500 group-hover:text-zinc-300' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h4.5v4.5h-4.5v-4.5zM15.75 12.75h4.5v4.5h-4.5v-4.5zM3.75 17.25h4.5v0a2.25 2.25 0 002.25-2.25v-3a2.25 2.25 0 012.25-2.25h2.25"/>
                    </svg>
                    <span x-show="sidebarOpen || mobileOpen" x-transition.opacity class="truncate">Workflows</span>
                </a>
            </div>

            {{-- Divider --}}
            <div class="px-3">
                <div class="border-t border-zinc-800/60"></div>
            </div>

            {{-- Finanzen --}}
            <div class="px-3">
                <p x-show="sidebarOpen || mobileOpen" x-transition.opacity
                   class="mb-1.5 px-2 text-xs font-semibold uppercase tracking-widest text-zinc-600">
                    Finanzen
                </p>
                @php $costsActive = request()->routeIs('costs.*'); @endphp
                <a href="{{ route('costs.index') }}"
                   @click="mobileOpen = false"
                   class="group flex items-center gap-3 rounded-lg px-2 py-2 text-sm font-medium transition-all
                          {{ $costsActive ? 'bg-emerald-600/15 text-emerald-400 ring-1 ring-emerald-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }}">
                    <svg class="h-[18px] w-[18px] shrink-0 {{ $costsActive ? 'text-emerald-400' : 'text-zinc-500 group-hover:text-zinc-300' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-show="sidebarOpen || mobileOpen" x-transition.opacity class="truncate">Kosten</span>
                </a>
            </div>

            {{-- Divider --}}
            <div class="px-3">
                <div class="border-t border-zinc-800/60"></div>
            </div>

            {{-- Public Boards --}}
            @auth
                @php
                    $publicUsers = \App\Models\User::query()
                        ->public()
                        ->where('id', '!=', auth()->id())
                        ->orderBy('name')
                        ->get(['id', 'name']);
                @endphp
                @if ($publicUsers->isNotEmpty())
                    <div class="px-3">
                        <p x-show="sidebarOpen || mobileOpen" x-transition.opacity
                           class="mb-1.5 px-2 text-xs font-semibold uppercase tracking-widest text-zinc-600">
                            Public Boards
                        </p>
                        @foreach ($publicUsers as $pu)
                            @php $active = request()->routeIs('dashboard.public') && request()->route('user')?->id === $pu->id; @endphp
                            <a href="{{ route('dashboard.public', $pu) }}"
                               @click="mobileOpen = false"
                               class="group flex items-center gap-3 rounded-lg px-2 py-2 text-sm font-medium transition-all
                                      {{ $active ? 'bg-purple-600/15 text-purple-400 ring-1 ring-purple-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }}">
                                <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full bg-zinc-800 text-[10px] font-bold uppercase
                                             {{ $active ? 'text-purple-300' : 'text-zinc-400' }}">
                                    {{ strtoupper(substr($pu->name, 0, 1)) }}
                                </span>
                                <span x-show="sidebarOpen || mobileOpen" x-transition.opacity class="truncate">{{ $pu->name }}</span>
                            </a>
                        @endforeach
                    </div>

                    {{-- Divider --}}
                    <div class="px-3">
                        <div class="border-t border-zinc-800/60"></div>
                    </div>
                @endif
            @endauth

            {{-- System --}}
            <div class="px-3">
                <p x-show="sidebarOpen || mobileOpen" x-transition.opacity
                   class="mb-1.5 px-2 text-xs font-semibold uppercase tracking-widest text-zinc-600">
                    System
                </p>
                @php
                    $system = [
                        ['route' => 'alerts.index', 'label' => 'Alerts',  'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'],
                        ['route' => 'profile',      'label' => 'Profil',  'icon' => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z'],
                    ];
                @endphp
                @foreach ($system as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                       @click="mobileOpen = false"
                       class="group relative flex items-center gap-3 rounded-lg px-2 py-2 text-sm font-medium transition-all
                              {{ $active ? 'bg-zinc-800 text-zinc-100' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }}">
                        <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-zinc-300' : 'text-zinc-500 group-hover:text-zinc-300' }}"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span x-show="sidebarOpen || mobileOpen" x-transition.opacity class="truncate">{{ $item['label'] }}</span>
                        @if ($item['route'] === 'alerts.index')
                            @auth
                                @php $nb = app(\App\Services\AlertService::class)->getUnreadCount(auth()->user()); @endphp
                                @if ($nb > 0)
                                    <span class="ml-auto flex h-[18px] w-[18px] items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white shrink-0"
                                          x-show="sidebarOpen || mobileOpen">{{ min($nb, 9) }}{{ $nb > 9 ? '+' : '' }}</span>
                                    <span class="absolute right-1.5 top-1.5 flex h-2 w-2 items-center justify-center"
                                          x-show="!sidebarOpen && !mobileOpen">
                                        <span class="block h-2 w-2 rounded-full bg-red-500"></span>
                                    </span>
                                @endif
                            @endauth
                        @endif
                    </a>
                @endforeach
            </div>
        </nav>

        {{-- Bottom: collapse toggle (desktop) + logout --}}
        <div class="shrink-0 border-t border-zinc-800/80 p-2 space-y-1">
            <button @click="sidebarOpen = !sidebarOpen"
                    class="hidden lg:flex w-full items-center gap-3 rounded-lg px-2 py-2 text-xs text-zinc-600 hover:bg-zinc-800 hover:text-zinc-300 transition-colors">
                <svg class="h-[18px] w-[18px] shrink-0 transition-transform duration-300" :class="sidebarOpen ? '' : 'rotate-180'"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 9l-3 3m0 0l3 3m-3-3h7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity>Einklappen</span>
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-sm text-zinc-400 hover:bg-zinc-800 hover:text-red-400 transition-colors">
                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                    </svg>
                    <span x-show="sidebarOpen || mobileOpen" x-transition.opacity class="truncate">Abmelden</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Main content                                                        --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="flex flex-1 flex-col min-h-full transition-all duration-300"
         :class="{
             'lg:ml-60': sidebarOpen,
             'lg:ml-16': !sidebarOpen,
         }">

        {{-- Topbar --}}
        <header class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-3 border-b border-zinc-800/80 bg-zinc-950/90 backdrop-blur-sm px-4 sm:px-6">

            {{-- Hamburger (mobile only) --}}
            <button @click="mobileOpen = !mobileOpen"
                    class="lg:hidden flex items-center justify-center h-9 w-9 rounded-lg border border-zinc-800 bg-zinc-900 text-zinc-500 hover:text-zinc-100 hover:border-zinc-700 transition-all">
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            <h1 class="flex-1 text-base font-semibold text-zinc-100 truncate">{{ $title ?? 'Dashboard' }}</h1>

            <div class="flex items-center gap-2 sm:gap-3">
                {{-- Alerts bell --}}
                @auth
                    @php $unreadTopbar = app(\App\Services\AlertService::class)->getUnreadCount(auth()->user()); @endphp
                    <a href="{{ route('alerts.index') }}"
                       class="relative flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-500 hover:text-zinc-100 hover:border-zinc-700 transition-all">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                        </svg>
                        @if ($unreadTopbar > 0)
                            <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white shadow-lg">
                                {{ min($unreadTopbar, 9) }}{{ $unreadTopbar > 9 ? '+' : '' }}
                            </span>
                        @endif
                    </a>
                @endauth

                {{-- User chip --}}
                @auth
                <a href="{{ route('profile') }}"
                   class="flex items-center gap-2 sm:gap-2.5 rounded-lg border border-zinc-800 bg-zinc-900 px-2 sm:px-3 py-1.5 hover:border-zinc-700 transition-colors">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white shrink-0">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <span class="hidden sm:block text-sm font-medium text-zinc-300">{{ auth()->user()->name ?? '' }}</span>
                </a>
                @endauth
            </div>
        </header>

        {{-- Page --}}
        <main class="flex-1 p-4 sm:p-6">
            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-5 flex items-center gap-3 rounded-xl border border-green-800/60 bg-green-900/20 px-4 py-3 text-sm text-green-400"
                     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 flex items-center gap-3 rounded-xl border border-red-800/60 bg-red-900/20 px-4 py-3 text-sm text-red-400"
                     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
