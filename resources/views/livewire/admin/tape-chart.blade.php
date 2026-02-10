<?php

use Livewire\Volt\Component;
use App\Models\Room;
use App\Models\BookingItem;
use Carbon\Carbon;

new class extends Component {
    public $startDate;
    public $days = 14;

    public function mount()
    {
        $this->startDate = now()->format('Y-m-d');
    }

    public function moveNext()
    {
        $this->startDate = Carbon::parse($this->startDate)->addDays(7)->format('Y-m-d');
    }

    public function movePrev()
    {
        $this->startDate = Carbon::parse($this->startDate)->subDays(7)->format('Y-m-d');
    }

    public function with()
    {
        $start = Carbon::parse($this->startDate);
        $end = $start->copy()->addDays($this->days);
        $period = iterator_to_array($start->toPeriod($end->subDay()));

        $rooms = Room::with('roomType')->orderBy('room_number')->get();

        $items = BookingItem::with('booking')
            ->whereNotNull('room_id')
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        // Map items to a lookup table [room_id][date_string]
        $grid = [];
        foreach ($items as $item) {
            $grid[$item->room_id][$item->date->format('Y-m-d')] = $item;
        }

        return [
            'period' => $period,
            'rooms' => $rooms,
            'grid' => $grid,
        ];
    }
}; ?>

<div class="p-6 lg:p-8 space-y-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Tape Chart</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Visual room occupancy and assignments.</p>
        </div>

        <div class="flex items-center gap-2">
            <flux:button icon="chevron-left" wire:click="movePrev" />
            <flux:input type="date" wire:model.live="startDate" class="w-44" />
            <flux:button icon="chevron-right" wire:click="moveNext" />
        </div>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <div class="overflow-x-auto overflow-y-auto max-h-[70vh]">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900 sticky top-0 z-20">
                        <th
                            class="p-4 border-b border-r border-zinc-200 dark:border-zinc-800 text-left min-w-[150px] sticky left-0 z-30 bg-zinc-50 dark:bg-zinc-900">
                            Room info
                        </th>
                        @foreach ($period as $date)
                            <th
                                class="p-2 border-b border-zinc-200 dark:border-zinc-800 text-center min-w-[80px] {{ $date->isToday() ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <div class="text-[10px] uppercase font-bold text-zinc-400 leading-none mb-1">
                                    {{ $date->format('D') }}</div>
                                <div class="text-sm font-black text-zinc-900 dark:text-white">{{ $date->format('d') }}
                                </div>
                                <div class="text-[10px] text-zinc-500 leading-none">{{ $date->format('M') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rooms as $room)
                        <tr>
                            <td
                                class="p-4 border-b border-r border-zinc-100 dark:border-zinc-800 sticky left-0 z-10 bg-white dark:bg-zinc-900">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ $room->room_number }}</div>
                                <div class="text-[10px] text-zinc-500 uppercase tracking-widest">
                                    {{ $room->roomType->name }}</div>
                            </td>
                            @foreach ($period as $date)
                                @php
                                    $dateStr = $date->format('Y-m-d');
                                    $item = $grid[$room->id][$dateStr] ?? null;
                                @endphp
                                <td
                                    class="p-1 border-b border-r border-zinc-50 dark:border-zinc-800 h-16 {{ $date->isToday() ? 'bg-blue-50/30' : '' }}">
                                    @if ($item)
                                        <a href="{{ route('admin.bookings.show', $item->booking_id) }}" wire:navigate
                                            class="block h-full w-full rounded-lg p-2 text-[10px] flex flex-col justify-center transition-transform hover:scale-105 @if ($item->booking->status === 'CONFIRMED') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200 border border-green-200 dark:border-green-800 @else bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 @endif">
                                            <div class="font-bold truncate">{{ $item->booking->customer_name }}</div>
                                            <div class="opacity-70">#{{ $item->booking_id }}</div>
                                        </a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </flux:card>

    <div class="flex gap-6 text-xs items-center">
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-green-100 border border-green-200 dark:bg-green-900/40 rounded"></div>
            <span class="text-zinc-500">Confirmed Booking</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-zinc-100 border border-zinc-200 dark:bg-zinc-800 rounded"></div>
            <span class="text-zinc-500">Hold / Pending</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-blue-50 border border-blue-100 rounded"></div>
            <span class="text-zinc-500">Today</span>
        </div>
    </div>
</div>
