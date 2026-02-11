<?php

use Livewire\Volt\Component;
use App\Models\RoomType;
use App\Models\Room;
use App\Models\Availability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    // Form state
    public $room_type_id;
    public $start_date;
    public $end_date;
    public $mode = 'fixed'; // fixed, increase_percent, decrease_percent, increase_fixed, decrease_fixed
    public $price_value;
    public $selected_days = [0, 1, 2, 3, 4, 5, 6]; // 0=Sunday, 6=Saturday

    // Calendar state
    public $view_room_type_id;
    public $view_month;
    public $view_year;

    public function mount()
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addDays(7)->format('Y-m-d');
        $this->view_month = now()->month;
        $this->view_year = now()->year;

        $firstRoomType = RoomType::first();
        if ($firstRoomType) {
            $this->room_type_id = $firstRoomType->id;
            $this->view_room_type_id = $firstRoomType->id;
        }
    }

    public function with()
    {
        return [
            'roomTypes' => RoomType::all(),
            'calendarData' => $this->getCalendarData(),
            'overrides' => $this->getRecentOverrides(),
        ];
    }

    public function getCalendarData()
    {
        if (!$this->view_room_type_id) {
            return [];
        }

        $startOfMonth = Carbon::create($this->view_year, $this->view_month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $availabilities = Availability::where('room_type_id', $this->view_room_type_id)
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get()
            ->keyBy(fn($a) => $a->date->format('Y-m-d'));

        $roomType = RoomType::find($this->view_room_type_id);
        $basePrice = $roomType ? $roomType->base_price : 0;

        $days = [];
        $current = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $lastDay = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        while ($current <= $lastDay) {
            $formattedDate = $current->format('Y-m-d');
            $avail = $availabilities->get($formattedDate);

            $days[] = [
                'date' => $current->copy(),
                'price' => $avail ? $avail->price : $basePrice,
                'is_override' => !!$avail,
                'is_current_month' => $current->month == $this->view_month,
            ];
            $current->addDay();
        }

        return $days;
    }

    public function getRecentOverrides()
    {
        return Availability::with('roomType')
            ->where('date', '>=', now()->format('Y-m-d'))
            ->orderBy('date')
            ->limit(10)
            ->get();
    }

    public function setViewMonth($month, $year)
    {
        $this->view_month = $month;
        $this->view_year = $year;
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->view_year, $this->view_month, 1)->subMonth();
        $this->view_month = $date->month;
        $this->view_year = $date->year;
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->view_year, $this->view_month, 1)->addMonth();
        $this->view_month = $date->month;
        $this->view_year = $date->year;
    }

    public function save()
    {
        $this->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'price_value' => 'required|numeric',
            'selected_days' => 'required|array|min:1',
        ]);

        $roomType = RoomType::findOrFail($this->room_type_id);
        $basePrice = $roomType->base_price;
        $period = CarbonPeriod::create($this->start_date, $this->end_date);
        $defaultInventory = Room::where('room_type_id', $this->room_type_id)->count();

        $count = 0;
        foreach ($period as $date) {
            // Check day of week
            if (!in_array($date->dayOfWeek, $this->selected_days)) {
                continue;
            }

            $formattedDate = $date->format('Y-m-d');
            $finalPrice = $this->calculateFinalPrice($basePrice);

            $availability = Availability::where('room_type_id', $this->room_type_id)->whereDate('date', $formattedDate)->first();

            if ($availability) {
                $availability->update(['price' => $finalPrice]);
            } else {
                Availability::create([
                    'room_type_id' => $this->room_type_id,
                    'date' => $formattedDate,
                    'price' => $finalPrice,
                    'total_inventory' => $defaultInventory > 0 ? $defaultInventory : 1,
                    'booked_count' => 0,
                    'blocked_count' => 0,
                ]);
            }
            $count++;
        }

        $this->dispatch('flux-toast', variant: 'success', message: "Updated prices for $count days.");
    }

    protected function calculateFinalPrice($basePrice)
    {
        return match ($this->mode) {
            'fixed' => $this->price_value,
            'increase_percent' => $basePrice * (1 + $this->price_value / 100),
            'decrease_percent' => $basePrice * (1 - $this->price_value / 100),
            'increase_fixed' => $basePrice + $this->price_value,
            'decrease_fixed' => $basePrice - $this->price_value,
            default => $this->price_value,
        };
    }

    public function resetPrice($id)
    {
        $avail = Availability::findOrFail($id);
        $avail->delete();
        $this->dispatch('flux-toast', variant: 'success', message: 'Price reset to base.');
    }
}; ?>

<div class="p-8 max-w-7xl mx-auto space-y-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" class="mb-1">{{ __('Advanced Pricing Strategy') }}</flux:heading>
            <flux:subheading>
                {{ __('Optimize your revenue with granular control over seasonal and demand-based rates.') }}
            </flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Update Form -->
        <div class="lg:col-span-1">
            <flux:card class="space-y-6 sticky top-8">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-indigo-50 dark:bg-indigo-950 rounded-lg text-indigo-600 dark:text-indigo-400">
                        <flux:icon.bolt class="size-5" />
                    </div>
                    <flux:heading size="lg">{{ __('Bulk Price Update') }}</flux:heading>
                </div>

                <flux:separator />

                <form wire:submit="save" class="space-y-6">
                    <flux:field>
                        <flux:label>{{ __('Target Room Type') }}</flux:label>
                        <flux:select wire:model.live="room_type_id" placeholder="{{ __('Select a room type...') }}">
                            @foreach ($roomTypes as $type)
                                <flux:select.option value="{{ $type->id }}">{{ $type->name }}
                                    (฿{{ number_format($type->base_price) }})</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="room_type_id" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input label="{{ __('From') }}" type="date" wire:model="start_date" />
                        <flux:input label="{{ __('To') }}" type="date" wire:model="end_date" />
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Applicable Days') }}</flux:label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $idx => $label)
                                <label class="flex-1">
                                    <input type="checkbox" wire:model="selected_days" value="{{ $idx }}"
                                        class="sr-only peer">
                                    <div
                                        class="flex items-center justify-center h-10 rounded-lg border border-zinc-200 dark:border-zinc-700 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 cursor-pointer text-xs font-bold transition-all">
                                        {{ $label }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <flux:error name="selected_days" />
                    </flux:field>

                    <flux:separator variant="subtle" />

                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>{{ __('Adjustment Mode') }}</flux:label>
                            <flux:select wire:model.live="mode">
                                <flux:select.option value="fixed">{{ __('Set Fixed Price') }}</flux:select.option>
                                <flux:select.option value="increase_percent">{{ __('Increase by %') }}
                                </flux:select.option>
                                <flux:select.option value="decrease_percent">{{ __('Decrease by %') }}
                                </flux:select.option>
                                <flux:select.option value="increase_fixed">{{ __('Increase by Fixed Amount') }}
                                </flux:select.option>
                                <flux:select.option value="decrease_fixed">{{ __('Decrease by Fixed Amount') }}
                                </flux:select.option>
                            </flux:select>
                        </flux:field>

                        <flux:field>
                            <flux:label>
                                {{ match ($mode) {
                                    'fixed' => __('Exact Price (฿)'),
                                    'increase_percent', 'decrease_percent' => __('Percentage (%)'),
                                    default => __('Amount (฿)'),
                                } }}
                            </flux:label>
                            <flux:input type="number" step="0.01" wire:model="price_value" placeholder="0.00" />
                            <flux:error name="price_value" />
                        </flux:field>
                    </div>

                    <flux:button type="submit" variant="primary" class="w-full h-12">{{ __('Apply Changes') }}
                    </flux:button>
                </form>
            </flux:card>
        </div>

        <!-- Calendar & Overrides -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Calendar Grid -->
            <flux:card>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div class="flex items-center gap-4">
                        <flux:select wire:model.live="view_room_type_id" class="w-48">
                            @foreach ($roomTypes as $type)
                                <flux:select.option value="{{ $type->id }}">{{ $type->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 rounded-lg p-1">
                            <flux:button variant="ghost" size="sm" icon="chevron-left"
                                wire:click="previousMonth" />
                            <div class="px-4 font-bold text-sm min-w-[120px] text-center">
                                {{ Carbon::create($view_year, $view_month, 1)->format('F Y') }}
                            </div>
                            <flux:button variant="ghost" size="sm" icon="chevron-right" wire:click="nextMonth" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs">
                        <div class="flex items-center gap-2">
                            <div class="size-2 rounded-full bg-indigo-500"></div> {{ __('Override') }}
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="size-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></div> {{ __('Base') }}
                        </div>
                    </div>
                </div>

                <div
                    class="grid grid-cols-7 gap-px bg-zinc-200 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                        <div
                            class="bg-zinc-50 dark:bg-zinc-800 py-2 text-center text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                            {{ $dayName }}
                        </div>
                    @endforeach

                    @foreach ($calendarData as $day)
                        <div @class([
                            'min-h-[80px] p-2 flex flex-col justify-between transition-colors',
                            'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white' =>
                                $day['is_current_month'],
                            'bg-zinc-50 dark:bg-zinc-800/50 text-zinc-400' => !$day['is_current_month'],
                        ])>
                            <span class="text-xs font-medium">{{ $day['date']->day }}</span>
                            <div class="text-right">
                                @if ($day['is_override'])
                                    <div
                                        class="text-[10px] bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300 px-1 py-0.5 rounded inline-block font-bold">
                                        ฿{{ number_format($day['price']) }}
                                    </div>
                                @else
                                    <div class="text-[10px] text-zinc-400">
                                        ฿{{ number_format($day['price']) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </flux:card>

            <!-- Recent Overrides List -->
            <flux:card>
                <div class="flex items-center justify-between mb-6">
                    <flux:heading size="lg">{{ __('Active Price Overrides') }}</flux:heading>
                    <flux:badge variant="outline" size="sm" color="zinc">{{ $overrides->count() }} Upcoming
                    </flux:badge>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-zinc-500 border-b border-zinc-100 dark:border-zinc-800">
                                <th class="pb-3 font-medium">{{ __('Date') }}</th>
                                <th class="pb-3 font-medium">{{ __('Room Type') }}</th>
                                <th class="pb-3 font-medium">{{ __('Special Price') }}</th>
                                <th class="pb-3 text-right font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                            @forelse($overrides as $override)
                                <tr>
                                    <td class="py-4 font-medium">{{ $override->date->format('d M Y') }}</td>
                                    <td class="py-4 text-zinc-500">{{ $override->roomType->name }}</td>
                                    <td class="py-4">
                                        <span
                                            class="font-bold text-indigo-600 dark:text-indigo-400">฿{{ number_format($override->price) }}</span>
                                    </td>
                                    <td class="py-4 text-right">
                                        <flux:button variant="ghost" icon="x-mark" size="sm"
                                            wire:click="resetPrice({{ $override->id }})"
                                            wire:confirm="Reset this day to base price?" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-zinc-500 italic">
                                        {{ __('No active price overrides found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    </div>
</div>
