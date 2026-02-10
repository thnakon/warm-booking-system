<?php

use Livewire\Volt\Component;
use App\Models\Booking;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $status = '';

    public function with()
    {
        $query = Booking::query()
            ->with(['items.roomType'])
            ->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', '%' . $this->search . '%')
                    ->orWhere('customer_email', 'like', '%' . $this->search . '%')
                    ->orWhere('id', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return [
            'bookings' => $query->paginate(10),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }
}; ?>

<div class="p-6 lg:p-8 space-y-8">
    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Reservations</h1>
            <p class="text-zinc-500 dark:text-zinc-400">View and manage all guest bookings.</p>
        </div>

        <flux:button :href="route('admin.bookings.create')" variant="primary" icon="plus" wire:navigate>New Booking
        </flux:button>
    </div>

    <flux:card class="p-6">
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, email, or ID..."
                    icon="magnifying-glass" />
            </div>
            <div class="w-full md:w-48">
                <flux:select wire:model.live="status" placeholder="All Status">
                    <flux:select.option value="">All Status</flux:select.option>
                    <flux:select.option value="HOLD">Hold</flux:select.option>
                    <flux:select.option value="CONFIRMED">Confirmed</flux:select.option>
                    <flux:select.option value="CANCELLED">Cancelled</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr
                        class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-100 dark:border-zinc-800">
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Guest</th>
                        <th class="px-6 py-4">Dates</th>
                        <th class="px-6 py-4">Room Type</th>
                        <th class="px-6 py-4 text-right">Total</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($bookings as $booking)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-zinc-500">
                                #{{ $booking->id }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ $booking->customer_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $booking->customer_email }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ $booking->items->first()?->date->format('d M') ?? 'N/A' }} -
                                    {{ $booking->items->last()?->date->addDay()->format('d M Y') ?? 'N/A' }}
                                </div>
                                <div class="text-xs text-zinc-500">
                                    {{ $booking->items->count() }} nights
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                {{ $booking->items->first()?->roomType?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-zinc-900 dark:text-white">
                                ฿{{ number_format($booking->total_price) }}
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge size="sm"
                                    :color="match($booking->status) {
                                                                                                                                                                                    'CONFIRMED' => 'green',
                                                                                                                                                                                    'CANCELLED' => 'red',
                                                                                                                                                                                    'HOLD' => 'zinc',
                                                                                                                                                                                    default => 'zinc'
                                                                                                                                                                                }">
                                    {{ $booking->status }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <flux:button :href="route('admin.bookings.show', $booking)" variant="ghost"
                                    size="sm" icon="eye" wire:navigate />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                                No bookings found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $bookings->links() }}
        </div>
    </flux:card>
</div>
