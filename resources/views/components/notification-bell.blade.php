{{--
    The notification bell shown in the navigation bar of both dispatcher panels.

    The admin and the associate are both told when a driver answers a ride, and
    both read the same rows through the same named routes, so this is a single
    component used by both navigation partials.
--}}
<div class="mb-3 relative"
     x-data="notificationBell({
         index: @js(route('notifications.index')),
         read: @js(route('notifications.read', ['notification' => '__id__'])),
         readAll: @js(route('notifications.read-all')),
     })"
     x-init="init()">

    <button @click="toggle()"
            class="relative w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800/50 transition-colors">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span>Notifications</span>
        <span x-show="unreadCount > 0" x-text="unreadCount > 9 ? '9+' : unreadCount"
              class="ml-auto min-w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center px-1"></span>
    </button>

    {{-- Dropdown panel --}}
    <div x-show="open" @click.away="open = false"
         class="absolute bottom-full left-0 w-80 mb-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-50 overflow-hidden"
         style="display:none;">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800">
            <span class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</span>
            <button @click="markAllRead()" x-show="unreadCount > 0"
                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Mark all read</button>
        </div>
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
            <template x-if="notifications.length === 0">
                <p class="px-4 py-8 text-center text-sm text-gray-400" x-text="loading ? 'Loading…' : 'No new notifications'"></p>
            </template>
            <template x-for="n in notifications" :key="n.id">
                <a :href="n.url || '#'" @click="markRead(n.id)"
                   class="flex gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors block">
                    <div class="mt-1.5 w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="n.title"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-snug" x-text="n.body"></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="n.created_at"></p>
                    </div>
                </a>
            </template>
        </div>
    </div>
</div>
