<?php

use Livewire\Volt\Component;
use App\Services\BookingService;
use Carbon\Carbon;

new class extends Component {
    public $checkIn;
    public $checkOut;
    public $results = [];

    public function mount()
    {
        $this->checkIn = now()->format('Y-m-d');
        $this->checkOut = now()->addDays(2)->format('Y-m-d');
        $this->executeSearch(new BookingService());
    }

    public function executeSearch(BookingService $service)
    {
        $this->results = $service->getAvailableRoomTypes($this->checkIn, $this->checkOut);
    }

    public function rendering($view, $data)
    {
        $view->layout('components.layouts.public');
    }
}; ?>

<div class="max-w-7xl mx-auto py-16 px-6">
    <div class="mb-12">
        <flux:badge color="zinc" variant="outline" class="mb-4 uppercase tracking-widest text-xs font-bold">Step 1:
            Choose Your Room</flux:badge>
        <h1 class="text-4xl font-serif font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Find Your Perfect
            Sanctuary</h1>
        <p class="mt-4 text-zinc-500 dark:text-zinc-400 max-w-2xl leading-relaxed">
            Select your travel dates to see available accommodations and exclusive rates.
            All reservations include our signature breakfast and spa access.
        </p>
    </div>

    <div
        class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl p-8 shadow-2xl shadow-zinc-200/50 dark:shadow-none mb-16">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end">
            <flux:input label="Check-in Date" type="date" wire:model.live="checkIn"
                min="{{ now()->format('Y-m-d') }}" />
            <flux:input label="Check-out Date" type="date" wire:model.live="checkOut" min="{{ $checkIn }}" />
            <flux:button variant="primary" wire:click="executeSearch"
                class="h-10 text-sm font-bold tracking-widest uppercase">
                Check Availability
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-12">
        @forelse ($results as $roomType)
            <div
                class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-2/5 aspect-[4/3] lg:aspect-auto relative overflow-hidden">
                        @php
                            $img = match ($roomType->id) {
                                1 => asset('images/room_luxury.png'),
                                default => asset('images/room_deluxe.png'),
                            };
                        @endphp
                        <img src="{{ $img }}" alt="{{ $roomType->name }}" class="w-full h-full object-cover">
                    </div>

                    <div class="flex-1 p-8 lg:p-12 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-3xl font-serif font-bold text-zinc-900 dark:text-white">
                                    {{ $roomType->name }}</h3>
                                <flux:badge size="sm" variant="outline" class="uppercase tracking-tighter">
                                    {{ $roomType->capacity }} Guests Max</flux:badge>
                            </div>
                            <p class="text-zinc-500 dark:text-zinc-400 leading-relaxed text-lg mb-8">
                                {{ $roomType->description ?? 'Experience the pinnacle of coastal luxury in our meticulously designed rooms, where every detail has been curated for your ultimate comfort.' }}
                            </p>

                            <div class="flex flex-wrap gap-4 text-sm text-zinc-400">
                                <span class="flex items-center gap-2">
                                    <flux:icon.wifi class="size-4" /> Free High-Speed WiFi
                                </span>
                                <span class="flex items-center gap-2">
                                    <flux:icon.sun class="size-4" /> Ocean View
                                </span>
                                <span class="flex items-center gap-2">
                                    <flux:icon.sparkles class="size-4" /> Daily Housekeeping
                                </span>
                            </div>
                        </div>

                        <div
                            class="mt-12 flex flex-col sm:flex-row items-center justify-between pt-8 border-t border-zinc-50 dark:border-zinc-800 gap-6">
                            <div>
                                <div class="text-sm text-zinc-400 uppercase tracking-widest mb-1 font-bold">TOTAL
                                    FOR STAY</div>
                                <div class="flex items-baseline gap-1">
                                    <span
                                        class="text-4xl font-black text-zinc-900 dark:text-white">฿{{ number_format($roomType->total_price) }}</span>
                                    <span class="text-zinc-400 italic">inc. taxes</span>
                                </div>
                            </div>

                            <flux:button variant="primary" class="h-14 px-10 text-lg font-bold w-full sm:w-auto"
                                href="{{ route('booking.checkout', [
                                    'roomTypeId' => $roomType->id,
                                    'checkIn' => $this->checkIn,
                                    'checkOut' => $this->checkOut,
                                ]) }}"
                                wire:navigate>
                                SELECT ROOM
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div
                class="flex flex-col items-center justify-center py-32 bg-zinc-50 dark:bg-zinc-900/40 rounded-[3rem] border-2 border-dashed border-zinc-200 dark:border-zinc-800">
                <div
                    class="size-20 bg-white dark:bg-zinc-800 rounded-full flex items-center justify-center mb-6 shadow-sm">
                    <flux:icon.calendar-days class="size-10 text-zinc-300" />
                </div>
                <h3 class="text-2xl font-serif font-bold text-zinc-900 dark:text-zinc-100">No availability for these
                    dates</h3>
                <p class="text-zinc-500 mt-2 max-w-xs text-center">We are currently fully booked for your selected
                    period. Please try other dates.</p>
            </div>
        @endforelse
    </div>
</div>
