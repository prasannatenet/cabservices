@props([
    'name',
    'value' => null,
    'required' => false,
])

@php
    /**
     * The browser's native time input shows 24-hour time, so the time is picked
     * from three scroll wheels that always read 12-hour, while the hidden input
     * keeps submitting the `H:i` value the booking expects.
     *
     * The wheels hold real times only. An empty row was tried and removed: when
     * it came to the centre of the wheel it was invisible, so all that was left
     * above the highlighted row was the centring padding, which read as dead
     * space. A field with nothing chosen now opens on the current time.
     */
    $hours = ['12', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'];
    $minutes = array_map(fn ($minute) => str_pad($minute, 2, '0', STR_PAD_LEFT), range(0, 59));
    $meridiems = ['AM', 'PM'];

    $stored = filled($value) ? trim((string) $value) : '';
    $parsed = preg_match('/^(\d{1,2}):(\d{2})$/', $stored, $matches) === 1;

    if ($parsed) {
        $hour24 = (int) $matches[1];
        $minute = $matches[2];
    } else {
        // Nothing stored. The wheels fall back to the browser's own clock when
        // the page opens, so the default is the real time and not the time the
        // server happened to render the page at. These placeholders only stop
        // Alpine showing an empty wheel for the moment before it takes over.
        $hour24 = (int) now()->format('G');
        $minute = str_pad(now()->format('i'), 2, '0', STR_PAD_LEFT);
    }

    $meridiemIndex = $hour24 >= 12 ? 1 : 0;
    $hour12 = str_pad(($hour24 % 12) === 0 ? 12 : $hour24 % 12, 2, '0', STR_PAD_LEFT);
    $hourIndex = (int) array_search($hour12, $hours, true);
    $minuteIndex = (int) array_search($minute, $minutes, true);

    /** Row height in pixels, shared by the wheels and the scroll maths. */
    $row = 44;
@endphp

<div {{ $attributes->merge(['class' => 'w-full relative']) }}
     x-data="{
         open: false,
         row: {{ $row }},
         hours: @js($hours),
         minutes: @js($minutes),
         meridiems: @js($meridiems),
         hourIndex: {{ $hourIndex }},
         minuteIndex: {{ $minuteIndex }},
         meridiemIndex: {{ $meridiemIndex }},
         // A time the visitor already chose is never overwritten by the clock.
         hasStoredTime: @js($parsed),
         get value() {
             return this.hours[this.hourIndex] + ':' + this.minutes[this.minuteIndex];
         },
         get label() {
             return this.value + ' ' + this.meridiems[this.meridiemIndex];
         },
         /**
          * Point the wheels at the browser's own clock, so the default is the
          * real time where the visitor is rather than the time the server
          * rendered the page at.
          */
         useClock() {
             const now = new Date();

             this.hourIndex = this.hours.indexOf(String(now.getHours() % 12 || 12).padStart(2, '0'));
             this.minuteIndex = Number(now.getMinutes());
             this.meridiemIndex = now.getHours() >= 12 ? 1 : 0;

             return this;
         },
         init() {
             if (!this.hasStoredTime) {
                 this.useClock();
             }

             this.reveal();
         },
         toggle() {
             this.open = !this.open;

             if (this.open) {
                 this.reveal();
             }
         },
         /**
          * The wheels can only be scrolled once they are on screen, so the
          * stored choice has to be put back in place every time the panel opens.
          */
         reveal() {
             this.$nextTick(() => {
                 if (!this.$refs.hour) {
                     return;
                 }

                 this.$refs.hour.scrollTop = this.hourIndex * this.row;
                 this.$refs.minute.scrollTop = this.minuteIndex * this.row;
                 this.$refs.meridiem.scrollTop = this.meridiemIndex * this.row;
             });
         },
         /**
          * Move a wheel one row and commit the move at once, so the arrow keys
          * and a click both feel the same.
          */
         move(column, direction) {
             const length = this[column + 's'].length;
             const next = Math.min(Math.max(this[column + 'Index'] + direction, 0), length - 1);

             this.$refs[column].scrollTo({ top: next * this.row, behavior: 'smooth' });
             this[column + 'Index'] = next;
         },
     }"
     @click.outside="open = false"
     @keydown.escape.window="open = false">
    <input type="hidden" name="{{ $name }}" x-bind:value="value">

    {{-- The trigger. Shows the chosen time and opens the wheels. --}}
    <button type="button"
            x-on:click="toggle()"
            x-bind:aria-expanded="open"
            aria-haspopup="listbox"
            @if ($required) aria-required="true" @endif
            class="w-full px-4 py-3.5 flex items-center justify-between gap-2 bg-gray-50/50 dark:bg-gray-900/50 border text-gray-900 dark:text-white rounded-xl text-left transition-colors"
            x-bind:class="open
                ? 'border-primary-500 ring-2 ring-primary-500/30'
                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
        <span class="font-semibold tabular-nums" x-text="label"></span>

        {{-- <span class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
            <span x-text="open ? 'Close' : 'Change'"></span>
            <svg class="w-4 h-4 transition-transform" x-bind:class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </span> --}}
    </button>

    {{-- The wheels. Hidden until the trigger is pressed. --}}
    <div x-show="open" x-cloak
         class="absolute left-0 right-0 top-full z-30 mt-1.5 rounded-xl bg-white dark:bg-[#161615] shadow-xl border border-gray-200 dark:border-gray-800/60 p-2">
        <div class="grid grid-cols-3 gap-1">
            @foreach (['hour' => $hours, 'minute' => $minutes, 'meridiem' => $meridiems] as $column => $choices)
                <div x-ref="{{ $column }}"
                     @scroll="{{ $column }}Index = Math.round($event.target.scrollTop / row)"
                     @scrollend="$event.target.scrollTo({ top: Math.round($event.target.scrollTop / row) * row, behavior: 'smooth' })"
                     @keydown.up.prevent="move('{{ $column }}', -1)"
                     @keydown.down.prevent="move('{{ $column }}', 1)"
                     tabindex="0"
                     role="listbox"
                     aria-label="{{ ucfirst($column) }}"
                     class="max-h-[176px] overflow-y-auto overscroll-contain py-[66px] focus:outline-none [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($choices as $index => $choice)
                        <div @click="$refs.{{ $column }}.scrollTo({ top: {{ $index }} * row, behavior: 'smooth' }); {{ $column }}Index = {{ $index }}"
                             role="option"
                             x-bind:aria-selected="{{ $column }}Index === {{ $index }}"
                             class="h-11 mx-0.5 flex items-center justify-center rounded-lg text-lg cursor-pointer select-none transition-colors"
                             :class="{{ $column }}Index === {{ $index }}
                                 ? 'bg-primary-600 text-white font-semibold shadow-md shadow-primary-500/30'
                                 : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200/70 dark:hover:bg-gray-800'">
                            {!! e($choice) !!}
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800/60 flex justify-end">
            <button type="button" x-on:click="open = false"
                    class="px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                Done
            </button>
        </div>
    </div>

    @error($name)
        <span class="block text-red-500 text-xs mt-1">{{ $message }}</span>
    @enderror
</div>
