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
}; ?>

<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Find Your Perfect Room</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Select your travel dates to see available accommodations and
            rates.</p>
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-sm mb-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <flux:input label="Check-in Date" type="date" wire:model="checkIn" min="{{ now()->format('Y-m-d') }}" />

            <flux:input label="Check-out Date" type="date" wire:model="checkOut" min="{{ $checkIn }}" />

            <div class="flex items-end">
                <flux:button variant="primary" wire:click="executeSearch" class="w-full h-10">
                    Check Availability
                </flux:button>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        @forelse ($results as $roomType)
            <flux:card class="overflow-hidden">
                <div class="flex flex-col md:flex-row">
                    <div
                        class="w-full md:w-1/3 aspect-video bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                        <flux:icon.home class="w-12 h-12 text-zinc-300 dark:text-zinc-700" />
                    </div>

                    <div class="flex-1 p-6 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $roomType->name }}</h3>
                                <div
                                    class="px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                    {{ $roomType->capacity }} Guests Max
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                {{ $roomType->description ?? 'Experience comfort and style in our well-appointed rooms.' }}
                            </p>
                        </div>

                        <div
                            class="mt-6 flex items-center justify-between pt-6 border-t border-zinc-100 dark:border-zinc-800">
                            <div>
                                <span class="text-2xl font-black text-zinc-900 dark:text-white">
                                    ฿{{ number_format($roomType->total_price) }}
                                </span>
                                <span class="text-sm text-zinc-500 ml-1">total for stay</span>
                            </div>

                            <flux:button variant="filled" class="font-semibold">
                                Select Room
                            </flux:button>
                        </div>
                    </div>
                </div>
            </flux:card>
        @empty
            <div
                class="flex flex-col items-center justify-center py-24 bg-zinc-50 dark:bg-zinc-950 rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-800">
                <flux:icon.calendar-days class="w-12 h-12 text-zinc-300 mb-4" />
                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">No availability for these dates</h3>
                <p class="text-sm text-zinc-500 mt-1">Try adjusting your dates or searching for a different room type.
                </p>
            </div>
        @endforelse
    </div>
</div>
