<?php

use Livewire\Volt\Component;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\BookingItem;
use Carbon\Carbon;

new class extends Component {
    public $startDate;
    public $days = 14;
    public $roomTypeId = '';

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

        $roomsQuery = Room::with('roomType')->orderBy('room_number');
        if ($this->roomTypeId) {
            $roomsQuery->where('room_type_id', $this->roomTypeId);
        }
        $rooms = $roomsQuery->get();

        $items = BookingItem::with(['booking'])
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
            'roomTypes' => RoomType::all(),
        ];
    }
}; ?>

<div class="p-8 max-w-full space-y-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <flux:heading size="xl" class="mb-1">{{ __('Interactive Tape Chart') }}</flux:heading>
            <flux:subheading>{{ __('Live room occupancy timeline with continuous stay visualizers.') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="w-48">
                <flux:select wire:model.live="roomTypeId" placeholder="{{ __('All Room Types') }}">
                    <flux:select.option value="">{{ __('All Room Types') }}</flux:select.option>
                    @foreach ($roomTypes as $type)
                        <flux:select.option value="{{ $type->id }}">{{ $type->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div
                class="flex items-center bg-zinc-100 dark:bg-zinc-800 rounded-lg p-1 border border-zinc-200 dark:border-zinc-700">
                <flux:button variant="ghost" icon="chevron-left" wire:click="movePrev" size="sm" />
                <flux:input type="date" wire:model.live="startDate"
                    class="!border-0 !bg-transparent !shadow-none w-36 text-sm" />
                <flux:button variant="ghost" icon="chevron-right" wire:click="moveNext" size="sm" />
            </div>
        </div>
    </div>

    <flux:card class="p-0 overflow-hidden relative shadow-2xl border-zinc-200 dark:border-zinc-800">
        <div class="overflow-x-auto overflow-y-auto max-h-[75vh]">
            <table class="w-full border-collapse table-fixed">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-900 sticky top-0 z-30 shadow-sm">
                        <th
                            class="p-4 border-b border-r border-zinc-200 dark:border-zinc-800 text-left w-[200px] min-w-[200px] sticky left-0 z-40 bg-zinc-50 dark:bg-zinc-900">
                            <flux:label class="uppercase tracking-widest text-[10px]">{{ __('Room & Tier') }}
                            </flux:label>
                        </th>
                        @foreach ($period as $date)
                            <th
                                class="p-3 border-b border-zinc-200 dark:border-zinc-800 text-center w-[100px] min-w-[100px] {{ $date->isToday() ? 'bg-indigo-50/50 dark:bg-indigo-950/30' : '' }}">
                                <div class="text-[10px] uppercase font-bold text-zinc-400 mb-0.5 tracking-tighter">
                                    {{ $date->format('D') }}</div>
                                <div class="text-base font-black text-zinc-900 dark:text-white leading-none">
                                    {{ $date->format('d') }}</div>
                                <div class="text-[9px] text-zinc-500 uppercase tracking-wide mt-0.5">
                                    {{ $date->format('M') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($rooms as $room)
                        <tr class="group hover:bg-zinc-50/30 dark:hover:bg-zinc-800/20">
                            <td
                                class="p-4 border-r border-zinc-100 dark:border-zinc-800 sticky left-0 z-20 bg-white dark:bg-zinc-900 group-hover:bg-zinc-50 dark:group-hover:bg-zinc-800/40 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="size-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-black text-zinc-600 dark:text-zinc-400 text-xs">
                                        {{ $room->room_number }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-zinc-900 dark:text-white truncate uppercase">
                                            {{ $room->roomType->name }}</div>
                                        <div class="text-[9px] text-zinc-400 truncate tracking-wide">
                                            ฿{{ number_format($room->roomType->base_price) }} / night</div>
                                    </div>
                                </div>
                            </td>
                            @php $skipCount = 0; @endphp
                            @foreach ($period as $dateIndex => $date)
                                @if ($skipCount > 0)
                                    @php
                                        $skipCount--;
                                        continue;
                                    @endphp
                                @endif

                                @php
                                    $dateStr = $date->format('Y-m-d');
                                    $item = $grid[$room->id][$dateStr] ?? null;
                                    $colspan = 1;

                                    if ($item) {
                                        // Look ahead to see how many continuous days this booking has for THIS room
                                        $currentBookingId = $item->booking_id;
                                        for ($i = $dateIndex + 1; $i < count($period); $i++) {
                                            $nextDateStr = $period[$i]->format('Y-m-d');
                                            $nextItem = $grid[$room->id][$nextDateStr] ?? null;

                                            if ($nextItem && $nextItem->booking_id === $currentBookingId) {
                                                $colspan++;
                                            } else {
                                                break;
                                            }
                                        }
                                        $skipCount = $colspan - 1;
                                    }
                                @endphp

                                <td colspan="{{ $colspan }}"
                                    class="p-1 border-r border-zinc-50 dark:border-zinc-800/40 h-20 relative {{ $date->isToday() ? 'bg-indigo-50/30 dark:bg-indigo-950/10' : '' }}">
                                    @if ($item)
                                        <flux:tooltip position="top" class="h-full">
                                            <a href="{{ route('admin.bookings.show', $item->booking_id) }}"
                                                wire:navigate
                                                class="block h-full w-full rounded-xl p-3 text-[10px] flex flex-col justify-center transition-all hover:ring-2 hover:ring-offset-2 hover:ring-indigo-500 @if ($item->booking->status === 'CONFIRMED') bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800 @else bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 @endif">

                                                <div class="flex items-center gap-1.5 mb-1">
                                                    @if ($item->booking->status === 'CONFIRMED')
                                                        <flux:icon.check-badge class="size-3" />
                                                    @else
                                                        <flux:icon.clock class="size-3" />
                                                    @endif
                                                    <span
                                                        class="font-black truncate tracking-wide text-[11px] uppercase">{{ $item->booking->customer_name }}</span>
                                                </div>

                                                <div class="flex items-center gap-3 opacity-60">
                                                    <span class="font-mono">#{{ $item->booking_id }}</span>
                                                    <span class="flex items-center gap-1">
                                                        <flux:icon.moon class="size-3" />
                                                        {{ $colspan }} night{{ $colspan > 1 ? 's' : '' }}
                                                    </span>
                                                </div>

                                                <!-- Check-in/out visual cues -->
                                                <div
                                                    class="absolute inset-y-0 left-0 w-1 rounded-l-xl @if ($item->booking->status === 'CONFIRMED') bg-emerald-400 @else bg-zinc-400 @endif">
                                                </div>
                                            </a>

                                            <flux:tooltip.content class="p-4 space-y-2 !max-w-xs">
                                                <div class="font-bold border-b border-zinc-100 pb-2 mb-2 truncate">
                                                    {{ $item->booking->customer_name }}</div>
                                                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[10px]">
                                                    <span class="text-zinc-400 uppercase tracking-tighter">Phone:</span>
                                                    <span class="font-mono">{{ $item->booking->customer_phone }}</span>
                                                    <span
                                                        class="text-zinc-400 uppercase tracking-tighter">Status:</span>
                                                    <span class="font-bold">{{ $item->booking->status }}</span>
                                                    <span
                                                        class="text-zinc-400 uppercase tracking-tighter">Payment:</span>
                                                    <span
                                                        class="font-bold text-emerald-600">{{ $item->booking->payment_status ?? 'PENDING' }}</span>
                                                </div>
                                            </flux:tooltip.content>
                                        </flux:tooltip>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </flux:card>

    <div
        class="flex flex-wrap gap-8 p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-2xl border border-zinc-100 dark:border-zinc-800 items-center justify-center md:justify-start">
        <div class="flex items-center gap-3">
            <div
                class="p-1 rounded-md bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-100 dark:border-emerald-800">
                <div class="size-3 rounded bg-emerald-400"></div>
            </div>
            <span
                class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">{{ __('Confirmed Stay') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="p-1 rounded-md bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                <div class="size-3 rounded bg-zinc-400 text-white"></div>
            </div>
            <span
                class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">{{ __('Hold / Pending') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="p-1 rounded-md bg-indigo-50/50 border border-indigo-100">
                <div class="size-3 rounded bg-indigo-200"></div>
            </div>
            <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">{{ __('Today') }}</span>
        </div>
    </div>
</div>
