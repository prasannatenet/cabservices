@php
    $isPreferred = $isPreferred ?? false;
@endphp
<div class="bg-white dark:bg-gray-900 rounded-3xl overflow-hidden shadow-sm hover:shadow-xl border {{ $isPreferred ? 'border-primary-500 shadow-primary-500/20' : 'border-gray-100 dark:border-gray-800' }} transition-all duration-300 hover:-translate-y-1 animate-slide-up" style="animation-delay: {{ $index * 0.1 }}s;">
    <div class="h-48 bg-gray-100 dark:bg-gray-800 relative">
        @if($vehicle->images->count() > 0)
            <div x-data="{ activeIndex: 0 }" class="w-full h-full relative group">
                @foreach($vehicle->images as $index => $img)
                    <img x-show="activeIndex === {{ $index }}" 
                         src="{{ asset('storage/' . $img->image_path) }}" 
                         alt="{{ $vehicle->name }}" 
                         class="w-full h-full object-cover">
                @endforeach
                
                @if($vehicle->images->count() > 1)
                    <!-- Prev Button -->
                    <button @click.prevent="activeIndex = activeIndex === 0 ? {{ $vehicle->images->count() - 1 }} : activeIndex - 1" 
                            type="button"
                            class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <!-- Next Button -->
                    <button @click.prevent="activeIndex = activeIndex === {{ $vehicle->images->count() - 1 }} ? 0 : activeIndex + 1" 
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                    
                    <!-- Indicators -->
                    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
                        @foreach($vehicle->images as $index => $img)
                            <div class="w-1.5 h-1.5 rounded-full transition-colors" 
                                 :class="activeIndex === {{ $index }} ? 'bg-white' : 'bg-white/50'"></div>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="w-full h-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
            </div>
        @endif
        
        @if($isPreferred)
            <div class="absolute top-4 left-4 bg-primary-600/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-white shadow-sm flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                Preferred Match
            </div>
        @endif

        @if($vehicle->is_from_nearby_city ?? false)
            <div class="absolute top-4 left-4 bg-amber-500/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-white shadow-sm flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                {{ $vehicle->city->name }}
            </div>
        @endif

        <div class="absolute top-4 right-4 bg-white/90 dark:bg-gray-900/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-gray-900 dark:text-white shadow-sm">
            {{ $vehicle->vehicle_type }}
        </div>
    </div>
    <div class="p-6">
        <h4 class="font-display font-bold text-xl text-gray-900 dark:text-white mb-1">{{ $vehicle->name }}</h4>
        <p class="text-sm text-gray-500 mb-4">{{ $vehicle->model }} &bull; {{ $vehicle->seating_capacity }} Seats</p>
        
        <div class="flex flex-wrap gap-2 mb-6">
            @foreach(explode(',', $vehicle->features) as $feature)
                @if(trim($feature))
                    <span class="px-2.5 py-1 bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs rounded-lg">{{ trim($feature) }}</span>
                @endif
            @endforeach
        </div>

        <form action="{{ route('booking.create') }}" method="GET">
            @foreach($searchParams as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
            <button type="submit" class="w-full py-3 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 hover:bg-primary-600 hover:text-white dark:hover:bg-primary-600 font-bold rounded-xl transition-colors duration-300">
                Select Vehicle
            </button>
        </form>
    </div>
</div>
