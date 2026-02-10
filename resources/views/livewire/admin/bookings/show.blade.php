<?php

use Livewire\Volt\Component;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Models\RoomType;

new class extends Component {
    public Booking $booking;
    public $status;

    public function mount(Booking $booking)
    {
        $this->booking = $booking->load(['items.roomType', 'items.room']);
        $this->status = $booking->status;
    }

    public function updateStatus()
    {
        $this->booking->update(['status' => $this->status]);
        session()->flash('success', 'Booking status updated successfully.');
    }

    public function assignRoom($itemId, $roomId)
    {
        $item = BookingItem::findOrFail($itemId);

        // Basic check: if room belongs to the correct type
        $room = Room::findOrFail($roomId);
        if ($room->room_type_id !== $item->room_type_id) {
            session()->flash('error', 'Invalid room type for this assignment.');
            return;
        }

        $item->update(['room_id' => $roomId]);
        $this->booking->load('items.room'); // Refresh
        session()->flash('success', 'Room #' . $room->room_number . ' assigned to item.');
    }

    public function getAvailableRooms($roomTypeId)
    {
        return Room::where('room_type_id', $roomTypeId)->get();
    }
}; ?>

<div class="max-w-5xl mx-auto py-10 px-4 space-y-8">
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item :href="route('admin.dashboard')">Admin</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.bookings.index')">Bookings</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>#{{ $booking->id }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-black text-zinc-900 dark:text-white">Reservation #{{ $booking->id }}</h1>
                <flux:badge
                    :color="match($booking->status) {
                                                            'CONFIRMED' => 'green',
                                                            'CANCELLED' => 'red',
                                                            'HOLD' => 'zinc',
                                                            default => 'zinc'
                                                        }">
                    {{ $booking->status }}</flux:badge>
            </div>
            <p class="text-zinc-500 mt-1 italic">Created on {{ $booking->created_at->format('d M Y, H:i') }}</p>
        </div>

        <div class="flex gap-3">
            <flux:select wire:model="status" class="w-40">
                <flux:select.option value="HOLD">Hold</flux:select.option>
                <flux:select.option value="CONFIRMED">Confirmed</flux:select.option>
                <flux:select.option value="CANCELLED">Cancelled</flux:select.option>
            </flux:select>
            <flux:button variant="primary" wire:click="updateStatus">Update Status</flux:button>
        </div>
    </div>

    @if (session()->has('success'))
        <div
            class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-900/30 rounded-xl text-sm text-green-600 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Guest & Payment info -->
        <div class="lg:col-span-1 space-y-6">
            <flux:card class="p-6">
                <h3 class="text-sm font-bold text-zinc-400 uppercase tracking-widest mb-4">Guest Details</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-zinc-500 block">Name</label>
                        <div class="font-bold text-zinc-900 dark:text-white">{{ $booking->customer_name }}</div>
                    </div>
                    <div>
                        <label class="text-xs text-zinc-500 block">Email</label>
                        <div class="font-medium text-zinc-700 dark:text-zinc-300">{{ $booking->customer_email }}</div>
                    </div>
                    <div>
                        <label class="text-xs text-zinc-500 block">Phone</label>
                        <div class="font-medium text-zinc-700 dark:text-zinc-300">{{ $booking->customer_phone }}</div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="p-6 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900">
                <h3 class="text-sm font-bold text-zinc-500 uppercase tracking-widest mb-4">Payment Summary</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Accommodation</span>
                        <span class="font-bold">฿{{ number_format($booking->total_price) }}</span>
                    </div>
                    <div class="flex justify-between pt-4 border-t border-zinc-800 dark:border-zinc-200">
                        <span class="font-bold uppercase tracking-widest text-xs">Total Amount</span>
                        <span class="text-2xl font-black">฿{{ number_format($booking->total_price) }}</span>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Room Items & Assignment -->
        <div class="lg:col-span-2 space-y-6">
            <flux:card class="p-0 overflow-hidden">
                <div class="p-6 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30">
                    <h3 class="font-bold">Reserved Nights & Room Assignment</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-100 dark:border-zinc-800">
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Room Type</th>
                                <th class="px-6 py-4">Room Assignment</th>
                                <th class="px-6 py-4 text-right">Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($booking->items as $item)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium">
                                        {{ $item->date->format('d M Y') }}
                                        <div class="text-xs text-zinc-500 font-normal italic">
                                            {{ $item->date->format('l') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        {{ $item->roomType->name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <flux:select
                                                wire:change="assignRoom({{ $item->id }}, $event.target.value)"
                                                class="w-32" dense>
                                                <flux:select.option value="">Unassigned</flux:select.option>
                                                @foreach ($this->getAvailableRooms($item->room_type_id) as $room)
                                                    <flux:select.option value="{{ $room->id }}"
                                                        :selected="$item->room_id == $room->id">
                                                        {{ $room->room_number }}
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>

                                            @if ($item->room_id)
                                                <flux:icon.check-circle class="w-4 h-4 text-green-500" />
                                            @else
                                                <flux:icon.exclamation-triangle class="w-4 h-4 text-orange-400" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        ฿{{ number_format($item->price) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </flux:card>

            <div
                class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-2xl border border-blue-100 dark:border-blue-900/30 flex gap-4">
                <flux:icon.information-circle class="w-6 h-6 text-blue-500 shrink-0" />
                <div>
                    <h4 class="font-bold text-blue-900 dark:text-blue-100 text-sm">About Room Assignment</h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        Assigning a room ensures the guest has a specific physical room number upon arrival.
                        In this MVP, room assignments are per-night to allow maximum flexibility.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
