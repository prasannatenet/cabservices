<x-public-layout>
    <!-- Hero Section -->
    <div class="relative overflow-hidden bg-white dark:bg-[#0a0a0a]">
        <!-- Background Gradients -->
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-primary-500/20 dark:bg-primary-500/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-accent/20 dark:bg-accent-dark/10 rounded-full blur-[120px] animate-pulse-slow" style="animation-delay: 2s;"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-32 relative">
            <div class="text-center animate-fade-in">
                <h1 class="font-display text-5xl md:text-7xl font-bold text-gray-900 dark:text-white mb-6 tracking-tight">
                    Ride in <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-accent">Comfort & Style</span>
                </h1>
                <p class="mt-4 text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto mb-12">
                    Experience the most reliable, secure, and premium cab service. Book your next journey with us in just a few clicks.
                </p>
            </div>

            <!-- Booking Search Form -->
            <div class="max-w-4xl mx-auto glass shadow-2xl rounded-3xl p-8 animate-slide-up relative z-10 border border-white/40 dark:border-white/10">
                <form action="{{ route('booking.search') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        
                        <!-- Pickup City -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Pickup City</label>
                            <select name="pickup_city_id" required class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ old('pickup_city_id') == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                                @endforeach
                            </select>
                            @error('pickup_city_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Drop City -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Drop City</label>
                            <select name="drop_city_id" required class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ old('drop_city_id') == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                                @endforeach
                            </select>
                            @error('drop_city_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Pickup Location -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Pickup Location (Address)</label>
                            <input type="text" name="pickup_location" value="{{ old('pickup_location') }}" required placeholder="Enter exact pickup address" class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            @error('pickup_location') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Drop Location -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Drop Location (Address)</label>
                            <input type="text" name="drop_location" value="{{ old('drop_location') }}" required placeholder="Enter exact drop address" class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            @error('drop_location') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Date & Time -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Pickup Date</label>
                            <input type="date" name="pickup_date" value="{{ old('pickup_date') }}" required min="{{ date('Y-m-d') }}" class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            @error('pickup_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Pickup Time</label>
                            <input type="time" name="pickup_time" value="{{ old('pickup_time') }}" required class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            @error('pickup_time') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Drop Date</label>
                            <input type="date" name="drop_date" value="{{ old('drop_date') }}" class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            @error('drop_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Drop Time</label>
                            <input type="time" name="drop_time" value="{{ old('drop_time') }}" class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            @error('drop_time') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Passengers & Service Type -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Passengers</label>
                            <input type="number" name="passengers" value="{{ old('passengers', 1) }}" min="1" required class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                            @error('passengers') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Service Type</label>
                            <select name="service_type_id" required class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                                <option value="">Select Service</option>
                                @foreach($serviceTypes as $service)
                                    <option value="{{ $service->id }}" {{ old('service_type_id') == $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                                @endforeach
                            </select>
                            @error('service_type_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Vehicle Preference -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vehicle Preference (Optional)</label>
                            <select name="vehicle_preference" class="w-full px-4 py-3.5 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-sm">
                                <option value="">Any Vehicle Type</option>
                                @foreach($vehicleCategories as $category)
                                    <option value="{{ $category->name }}" {{ old('vehicle_preference') == $category->name ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('vehicle_preference') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="lg:col-span-4 mt-6">
                            <button type="submit" class="w-full py-4 px-8 bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-500 hover:to-primary-400 text-white font-bold text-lg rounded-xl shadow-[0_0_20px_rgba(223,82,102,0.4)] hover:shadow-[0_0_25px_rgba(223,82,102,0.6)] transform hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                                <span class="relative z-10 flex items-center justify-center gap-2">
                                    Search Available Cabs
                                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Services Section -->
    <div id="services" class="py-24 bg-gray-50 dark:bg-[#0a0a0a] relative border-t border-gray-100 dark:border-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Our Premium Services</h2>
                <p class="text-gray-600 dark:text-gray-400">Tailored to meet all your transportation needs.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($serviceTypes as $service)
                <div class="group p-8 bg-white dark:bg-gray-900/50 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-2xl hover:shadow-primary-500/5 dark:hover:shadow-primary-500/10 transition-all duration-500 hover:-translate-y-2 relative overflow-hidden backdrop-blur-sm">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-primary-500/10 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="relative z-10 w-16 h-16 bg-gradient-to-br from-primary-50 to-primary-100 dark:from-gray-800 dark:to-gray-800 rounded-2xl flex items-center justify-center text-primary-600 dark:text-primary-400 mb-6 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="relative z-10 text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $service->name }}</h3>
                    <p class="relative z-10 text-gray-600 dark:text-gray-400 leading-relaxed">{{ $service->description ?: 'Enjoy our premium ' . strtolower($service->name) . ' service designed for your comfort.' }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-public-layout>
