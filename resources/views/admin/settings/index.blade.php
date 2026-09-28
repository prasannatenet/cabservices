<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
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

                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="mail_enabled" value="1" @checked(old('mail_enabled', $mailEnabled)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Enable email notifications
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Master switch. When off, no booking mail is sent at all.</span>
                            </span>
                        </label>


                        <div>
                            <label for="mail_from_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Address</label>
                            <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $fromAddress) }}" placeholder="bookings@example.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The sender shown on every notification. Leave empty to use the address configured in the environment.</p>
                            @error('mail_from_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="mail_from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Name</label>
                            <input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', $fromName) }}" placeholder="{{ config('app.name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('mail_from_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>


                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">Booking request</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Sent when a customer submits the booking form.</p>

                            <div class="space-y-3">
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="mail_notify_on_booking_request" value="1" @checked(old('mail_notify_on_booking_request', $notifyOnBookingRequest)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        Notify the operations team
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">Sends the new request to the recipients below.</span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-3">
                                    <input type="checkbox" name="mail_notify_customer" value="1" @checked(old('mail_notify_customer', $notifyCustomer)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        Acknowledge the customer
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">Only sent when the customer provided an email address.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="mt-4">
                                <label for="mail_booking_notification_recipients" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notification Recipients</label>
                                <textarea name="mail_booking_notification_recipients" id="mail_booking_notification_recipients" rows="3" placeholder="ops@example.com&#10;dispatch@example.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('mail_booking_notification_recipients', implode("\n", $recipients)) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">One address per line. When empty, every admin account is notified.</p>
                                @error('mail_booking_notification_recipients') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                @error('mail_booking_notification_recipients.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>


                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">Driver assignment</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Sent when a driver is assigned to a booking, and when the booking is confirmed.</p>

                            <label class="flex items-start gap-3">
                                <input type="checkbox" name="mail_notify_customer_on_driver_assigned" value="1" @checked(old('mail_notify_customer_on_driver_assigned', $notifyCustomerOnDriverAssigned)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    Notify the customer
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Sends the driver and vehicle details to the customer.</span>
                                </span>
                            </label>

                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                The assigned driver always receives his trip details on the address of his driver record.
                            </p>
                        </div>

                        <div class="flex justify-end gap-4">
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
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
        </div>
    </div>
</x-app-layout>