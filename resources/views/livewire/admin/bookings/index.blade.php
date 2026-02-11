<?php

use Livewire\Volt\Component;
use App\Models\Booking;
use App\Models\BookingItem;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $status = '';
    public $payment_status = '';
    public $filter_start_date = '';
    public $filter_end_date = '';

    public function with()
    {
        $query = Booking::query()
            ->with(['items.roomType'])
            ->latest();

        // Search by name, email, ID, or phone
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', '%' . $this->search . '%')
                    ->orWhere('customer_email', 'like', '%' . $this->search . '%')
                    ->orWhere('customer_phone', 'like', '%' . $this->search . '%')
                    ->orWhere('id', 'like', '%' . $this->search . '%');
            });
        }

        // Filter by Status
        if ($this->status) {
            $query->where('status', $this->status);
        }

        // Filter by Payment Status
        if ($this->payment_status) {
            $query->where('payment_status', $this->payment_status);
        }

        // Filter by Date Range (overlapping stays)
        if ($this->filter_start_date && $this->filter_end_date) {
            $query->whereHas('items', function ($q) {
                $q->whereBetween('date', [$this->filter_start_date, $this->filter_end_date]);
            });
        }

        return [
            'bookings' => $query->paginate(15),
            'stats' => $this->getStats(),
        ];
    }

    protected function getStats()
    {
        $todayStr = now()->toDateString();

        return [
            'checkInsToday' => Booking::whereHas('items', function ($q) use ($todayStr) {
                $q->where('date', $todayStr);
            })
                ->where('status', '!=', 'CANCELLED')
                ->count(),

            'checkOutsToday' => Booking::whereHas('items', function ($q) use ($todayStr) {
                $yesterday = now()->subDay()->toDateString();
                $q->where('date', $yesterday);
            })
                ->whereDoesntHave('items', function ($q) use ($todayStr) {
                    $q->where('date', $todayStr);
                })
                ->where('status', '!=', 'CANCELLED')
                ->count(),

            'new24h' => Booking::where('created_at', '>=', now()->subDay())->count(),
        ];
    }

    public function updateStatus($id, $newStatus)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => $newStatus]);
        $this->dispatch('flux-toast', variant: 'success', message: "Booking #$id status updated to $newStatus.");
    }

    public function updatePaymentStatus($id, $newStatus)
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['payment_status' => $newStatus]);
        $this->dispatch('flux-toast', variant: 'success', message: "Booking #$id payment marked as $newStatus.");
    }

    public function export()
    {
        // Re-calculate the filtered query (simplified for export)
        $query = Booking::query()->latest();
        if ($this->search) {
            $query->where('customer_name', 'like', '%' . $this->search . '%');
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }

        $bookings = $query->get();

        $csvHeader = ['ID', 'Customer', 'Email', 'Phone', 'Total', 'Status', 'Payment', 'Created'];
        $callback = function () use ($bookings, $csvHeader) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);
            foreach ($bookings as $b) {
                fputcsv($file, [$b->id, $b->customer_name, $b->customer_email, $b->customer_phone, $b->total_price, $b->status, $b->payment_status, $b->created_at]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'bookings-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function resetFilters()
    {
        $this->reset(['search', 'status', 'payment_status', 'filter_start_date', 'filter_end_date']);
    }

    public function updating()
    {
        $this->resetPage();
    }
}; ?>

<div class="p-8 max-w-7xl mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" class="mb-1">{{ __('Reservations Portfolio') }}</flux:heading>
            <flux:subheading>{{ __('Manage guest arrivals, departures, and payment reconciliations.') }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="outline" icon="document-arrow-down" wire:click="export">{{ __('Export CSV') }}
            </flux:button>
            <flux:button :href="route('admin.bookings.create')" variant="primary" icon="plus" wire:navigate>
                {{ __('New Booking') }}</flux:button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card class="flex items-center gap-4 py-6">
            <div
                class="size-12 rounded-full bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center text-emerald-600">
                <flux:icon.arrow-right-start-on-rectangle class="size-6" />
            </div>
            <div>
                <flux:heading size="lg">{{ $stats['checkInsToday'] }}</flux:heading>
                <flux:subheading size="sm">{{ __('Check-ins Today') }}</flux:subheading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4 py-6">
            <div
                class="size-12 rounded-full bg-blue-50 dark:bg-blue-950 flex items-center justify-center text-blue-600">
                <flux:icon.arrow-right-end-on-rectangle class="size-6" />
            </div>
            <div>
                <flux:heading size="lg">{{ $stats['checkOutsToday'] }}</flux:heading>
                <flux:subheading size="sm">{{ __('Check-outs Today') }}</flux:subheading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4 py-6">
            <div
                class="size-12 rounded-full bg-amber-50 dark:bg-amber-950 flex items-center justify-center text-amber-600">
                <flux:icon.clock class="size-6" />
            </div>
            <div>
                <flux:heading size="lg">{{ $stats['new24h'] }}</flux:heading>
                <flux:subheading size="sm">{{ __('New (Last 24h)') }}</flux:subheading>
            </div>
        </flux:card>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <!-- Filters Area -->
        <div class="p-6 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <flux:input wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search name, email, phone or ID...') }}" icon="magnifying-glass" />
                </div>
                <flux:select wire:model.live="status" placeholder="{{ __('Any Status') }}">
                    <flux:select.option value="">{{ __('Any Status') }}</flux:select.option>
                    <flux:select.option value="HOLD">{{ __('Hold') }}</flux:select.option>
                    <flux:select.option value="CONFIRMED">{{ __('Confirmed') }}</flux:select.option>
                    <flux:select.option value="CANCELLED">{{ __('Cancelled') }}</flux:select.option>
                </flux:select>
                <flux:select wire:model.live="payment_status" placeholder="{{ __('Any Payment') }}">
                    <flux:select.option value="">{{ __('Any Payment') }}</flux:select.option>
                    <flux:select.option value="PENDING">{{ __('Pending') }}</flux:select.option>
                    <flux:select.option value="PAID">{{ __('Paid') }}</flux:select.option>
                    <flux:select.option value="PARTIAL">{{ __('Partial') }}</flux:select.option>
                </flux:select>
                <div class="flex items-center gap-2">
                    <flux:input type="date" wire:model.live="filter_start_date" class="flex-1" />
                    <span class="text-zinc-400">-</span>
                    <flux:input type="date" wire:model.live="filter_end_date" class="flex-1" />
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr
                        class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-100 dark:border-zinc-800">
                        <th class="px-6 py-4">{{ __('Booking ID') }}</th>
                        <th class="px-6 py-4">{{ __('Guest Details') }}</th>
                        <th class="px-6 py-4">{{ __('Stay Schedule') }}</th>
                        <th class="px-6 py-4">{{ __('Billing') }}</th>
                        <th class="px-6 py-4">{{ __('Status') }}</th>
                        <th class="px-6 py-4">{{ __('Payment') }}</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($bookings as $booking)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-mono text-zinc-500">#{{ $booking->id }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ $booking->customer_name }}
                                </div>
                                <div class="text-xs text-zinc-500 flex items-center gap-2 mt-0.5">
                                    <flux:icon.envelope class="size-3" /> {{ $booking->customer_email }}
                                </div>
                                <div
                                    class="text-[10px] text-zinc-400 flex items-center gap-2 mt-0.5 uppercase tracking-wide">
                                    <flux:icon.phone class="size-3" /> {{ $booking->customer_phone }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-bold text-zinc-900 dark:text-white">
                                    {{ $booking->items->first()?->date->format('d M') }} -
                                    {{ $booking->items->last()?->date->addDay()->format('d M Y') }}
                                </div>
                                <div class="text-[10px] text-zinc-500 uppercase tracking-widest mt-0.5">
                                    {{ $booking->items->count() }} nights &bull;
                                    {{ $booking->items->first()?->roomType?->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-zinc-900 dark:text-white">
                                    ฿{{ number_format($booking->total_price) }}</div>
                                <div class="text-[10px] text-zinc-500 uppercase tracking-wide">
                                    {{ $booking->payment_method ?? 'Bank Transfer' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge size="sm"
                                    :color="match($booking->status) {
                                                                        'CONFIRMED' => 'emerald',
                                                                        'CANCELLED' => 'red',
                                                                        'HOLD' => 'zinc',
                                                                        default => 'zinc'
                                                                    }">
                                    {{ $booking->status }}</flux:badge>
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge size="sm"
                                    :variant="$booking->payment_status === 'PAID' ? 'solid' : 'outline'"
                                    :color="match($booking->payment_status) {
                                                                        'PAID' => 'emerald',
                                                                        'PARTIAL' => 'amber',
                                                                        default => 'zinc'
                                                                    }">
                                    {{ $booking->payment_status ?? 'PENDING' }}</flux:badge>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button :href="route('admin.bookings.show', $booking)" variant="ghost"
                                        size="sm" icon="eye" wire:navigate />

                                    <flux:dropdown>
                                        <flux:button variant="ghost" icon="ellipsis-horizontal" size="sm" />
                                        <flux:menu>
                                            <flux:menu.heading>{{ __('Quick Actions') }}</flux:menu.heading>
                                            <flux:menu.item icon="check-circle"
                                                wire:click="updateStatus({{ $booking->id }}, 'CONFIRMED')">
                                                {{ __('Confirm Stay') }}</flux:menu.item>
                                            <flux:menu.item icon="banknotes"
                                                wire:click="updatePaymentStatus({{ $booking->id }}, 'PAID')">
                                                {{ __('Mark as Paid') }}</flux:menu.item>
                                            <flux:menu.item icon="x-circle" variant="danger"
                                                wire:click="updateStatus({{ $booking->id }}, 'CANCELLED')">
                                                {{ __('Cancel Booking') }}</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-zinc-500">
                                    <flux:icon.magnifying-glass class="size-8 mb-2 opacity-20" />
                                    <p class="text-sm italic font-serif">No reservations found matching your criteria.
                                    </p>
                                    <flux:button variant="link" size="sm" wire:click="resetFilters"
                                        class="mt-2">Clear all filters</flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-6 border-t border-zinc-100 dark:border-zinc-800 bg-white/50 dark:bg-zinc-900/50">
            {{ $bookings->links() }}
        </div>
    </flux:card>
</div>
