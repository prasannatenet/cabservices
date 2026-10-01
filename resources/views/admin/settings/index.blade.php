<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="admin-card">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Company details</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 mb-6">
                The company name is shown in the panel headers, page titles, emails and the booking site.
            </p>

            <form action="{{ route('admin.settings.company.update') }}" method="POST" class="space-y-6 max-w-2xl">
                @csrf
                @method('PUT')

                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company name</label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $companyName) }}" placeholder="CabServices" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('company_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-4">
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Save Company</button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Admin account</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 mb-6">
                The email and password you sign in with. Leave the password blank to keep the current one.
            </p>

            <form action="{{ route('admin.settings.account.update') }}" method="POST" class="space-y-6 max-w-2xl">
                @csrf
                @method('PUT')

                <div>
                    <label for="admin_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Admin email</label>
                    <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email', Auth::user()->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('admin_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="admin_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New password</label>
                    <input type="password" name="admin_password" id="admin_password" autocomplete="new-password" placeholder="Leave blank to keep the current password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('admin_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm new password</label>
                    <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <div class="flex justify-end gap-4">
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Save Account</button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-card">
            <div class="p-6 text-gray-900 dark:text-gray-100">

                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Email notifications</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 mb-6">
                    Control when {{ config('app.name') }} sends mail for bookings and driver assignments.
                </p>

                <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6 max-w-2xl">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="mail_smtp_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Host</label>
                        <input type="text" name="mail_smtp_host" id="mail_smtp_host" value="{{ old('mail_smtp_host', $smtpHost) }}" placeholder="smtp.mailtrap.io" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('mail_smtp_host') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="mail_smtp_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Port</label>
                        <input type="text" name="mail_smtp_port" id="mail_smtp_port" value="{{ old('mail_smtp_port', $smtpPort) }}" placeholder="2525" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('mail_smtp_port') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="mail_smtp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Username</label>
                        <input type="text" name="mail_smtp_username" id="mail_smtp_username" value="{{ old('mail_smtp_username', $smtpUsername) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('mail_smtp_username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="mail_smtp_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SMTP Password / API Key</label>
                        <input type="password" name="mail_smtp_password" id="mail_smtp_password" value="" placeholder="{{ $smtpPasswordIsSet ? 'Leave blank to keep current password' : 'Enter password or API key' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @if($smtpPasswordIsSet)
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">✓ A password is currently saved. Leave blank to keep it unchanged.</p>
                        @endif
                        @error('mail_smtp_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="mail_smtp_encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Encryption</label>
                        <select name="mail_smtp_encryption" id="mail_smtp_encryption" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="tls" @selected(old('mail_smtp_encryption', $smtpEncryption) === 'tls')>STARTTLS (usually port 587)</option>
                            <option value="ssl" @selected(old('mail_smtp_encryption', $smtpEncryption) === 'ssl')>SSL/TLS (usually port 465)</option>
                        </select>
                        @error('mail_smtp_encryption') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="mail_from_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Address</label>
                        <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $fromAddress) }}" placeholder="bookings@example.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The sender shown on every notification.</p>
                        @error('mail_from_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="mail_from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Name</label>
                        <input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', $fromName) }}" placeholder="{{ config('app.name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('mail_from_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-4">
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Save Settings</button>
                    </div>
                </form>
            </div>
    </div>

    <div class="admin-card">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Send a test mail</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Sends a real message through the <span class="font-mono">{{ $mailer }}</span> mailer to confirm the configuration delivers.
                </p>

                <form action="{{ route('admin.settings.test-mail') }}" method="POST" class="flex flex-col sm:flex-row gap-3 sm:items-end max-w-2xl">
                    @csrf
                    <div class="flex-1">
                        <label for="test_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Send to</label>
                        <input type="email" name="email" id="test_email" value="{{ old('email', Auth::user()->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">Send Test Mail</button>
                </form>
            </div>
    </div>

    <div class="admin-card" x-data="desktopNotificationCard()">
            <div class="p-6 text-gray-900 dark:text-gray-100">

                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Desktop notifications</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 mb-6">
                    Raise a notification on your computer when a driver accepts or refuses a ride you dispatched.
                </p>

                <form action="{{ route('admin.settings.push.update') }}" method="POST" class="space-y-6 max-w-2xl">
                    @csrf
                    @method('PUT')

                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="push_enabled" value="1" @checked(old('push_enabled', $pushEnabled)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            Enable desktop notifications
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Master switch. When off, no desktop notification is sent at all.</span>
                        </span>
                    </label>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">Driver answers</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Sent to every admin, and to the associates managing the city the ride starts in.</p>

                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="push_notify_on_accept" value="1" @checked(old('push_notify_on_accept', $pushNotifyOnAccept)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                When a driver accepts
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Confirms the ride is going ahead.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 mt-3">
                            <input type="checkbox" name="push_notify_on_reject" value="1" @checked(old('push_notify_on_reject', $pushNotifyOnReject)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                When a driver refuses, or does not answer in 6 hours
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Carries the reason, and the toast stays on screen until dismissed.</span>
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-4">
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Save Settings</button>
                    </div>
                </form>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">This browser</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        The browser has to allow notifications, so every computer or phone subscribes on its own.
                    </p>

                    <p x-cloak x-show="message" :class="failed ? 'text-red-500' : 'text-green-600'" class="text-xs mb-4" x-text="message"></p>

                    <div class="flex flex-wrap gap-3">
                        <button type="button" x-on:click="enable"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                            Enable notifications
                        </button>
                        <button type="button" x-on:click="disable"
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                            Turn off
                        </button>
                    </div>

                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        A notification cannot appear while the browser is closed completely.
                    </p>
                </div>
            </div>
    </div>

</x-app-layout>
