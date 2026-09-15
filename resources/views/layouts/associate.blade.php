<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CabServices') }} - Associate Portal</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|outfit:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100 selection:bg-black selection:text-white dark:selection:bg-white dark:selection:text-black transition-colors duration-300 bg-gray-50 dark:bg-[#0a0a0a]">
        <!-- Alpine instance for sidebar state -->
        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            @include('associate.partials.navigation')

            <!-- Main Content -->
            <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                <!-- Mobile header -->
                <header class="sticky top-0 z-30 flex items-center justify-between px-4 py-4 bg-white dark:bg-[#161615] border-b border-gray-200 dark:border-gray-800 lg:hidden">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-black dark:bg-white rounded-lg flex items-center justify-center text-white dark:text-black">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                        <span class="font-display font-bold text-lg tracking-tight text-gray-900 dark:text-white">Associate Portal</span>
                    </div>
                    <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </header>

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white dark:bg-[#161615] border-b border-gray-200 dark:border-gray-800">
                        <div class="px-6 py-6 sm:px-8 max-w-7xl mx-auto">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="p-4 sm:p-6 lg:p-8 w-full max-w-7xl mx-auto">
                    @if (session('success'))
                        <div class="mb-6 p-4 rounded-xl border border-green-200 bg-green-50 text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-400 text-sm font-medium">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400 text-sm font-medium">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>