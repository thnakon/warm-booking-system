<?php

use Livewire\Volt\Component;
use App\Models\RoomType;
use App\Models\Room;
use App\Models\Availability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $room_type_id;
    public $start_date;
    public $end_date;
    public $price;

    public function mount()
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addDays(7)->format('Y-m-d');
    }

    public function with()
    {
        return [
            'roomTypes' => RoomType::all(),
        ];
    }

    public function save()
    {
        $this->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'price' => 'required|numeric|min:0',
        ]);

        $period = CarbonPeriod::create($this->start_date, $this->end_date);
        $defaultInventory = Room::where('room_type_id', $this->room_type_id)->count();

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $availability = Availability::where('room_type_id', $this->room_type_id)->whereDate('date', $formattedDate)->first();

            if ($availability) {
                $availability->update(['price' => $this->price]);
            } else {
                Availability::create([
                    'room_type_id' => $this->room_type_id,
                    'date' => $formattedDate,
                    'price' => $this->price,
                    'total_inventory' => $defaultInventory > 0 ? $defaultInventory : 1, // Fallback if no rooms defined yet
                    'booked_count' => 0,
                    'blocked_count' => 0,
                ]);
            }
        }

        $this->dispatch('flux-toast', variant: 'success', message: 'Prices updated successfully for the selected date range.');

        // Reset form (optional, or keep values for next update)
    }
}; ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Advanced Pricing') }}</flux:heading>
        <flux:subheading>{{ __('Set seasonal or weekend prices for your rooms.') }}</flux:subheading>
    </div>

    <flux:card class="max-w-2xl mx-auto space-y-6">
        <flux:heading size="lg">{{ __('Bulk Update Prices') }}</flux:heading>
        <flux:separator />

        <form wire:submit="save" class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Room Type') }}</flux:label>
                <flux:select wire:model="room_type_id" placeholder="{{ __('Select a room type...') }}">
                    @foreach ($roomTypes as $type)
                        <flux:select.option value="{{ $type->id }}">{{ $type->name }} ({{ __('Base') }}:
                            {{ number_format($type->base_price) }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="room_type_id" />
            </flux:field>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>{{ __('Start Date') }}</flux:label>
                    <flux:input type="date" wire:model="start_date" />
                    <flux:error name="start_date" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('End Date') }}</flux:label>
                    <flux:input type="date" wire:model="end_date" />
                    <flux:error name="end_date" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('New Price') }} ({{ __('Per Night') }})</flux:label>
                <flux:input type="number" step="0.01" wire:model="price" placeholder="e.g. 2500" />
                <flux:description>{{ __('This will override the base price for the selected dates.') }}
                </flux:description>
                <flux:error name="price" />
            </flux:field>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ __('Update Prices') }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
