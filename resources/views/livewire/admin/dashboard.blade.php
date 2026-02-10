<?php

use Livewire\Volt\Component;
use App\Models\Booking;
use App\Models\Availability;
use Carbon\Carbon;

new class extends Component {
    public function with()
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        // Arrivals: Min date of items is today
        $arrivals = Booking::whereHas('items', function ($query) use ($today) {
            $query->whereDate('date', $today);
        })
            ->with([
                'items' => function ($query) {
                    $query->orderBy('date', 'asc');
                },
            ])
            ->get()
            ->filter(function ($booking) use ($today) {
                return $booking->items->first()->date->format('Y-m-d') === $today;
            });

        // Departures: Max date of items was yesterday
        $departures = Booking::whereHas('items', function ($query) use ($yesterday) {
            $query->whereDate('date', $yesterday);
        })
            ->with([
                'items' => function ($query) {
                    $query->orderBy('date', 'desc');
                },
            ])
            ->get()
            ->filter(function ($booking) use ($yesterday) {
                return $booking->items->last()->date->format('Y-m-d') === $yesterday;
            });

        // Simpler logic for arrivals/departures might be needed if stay is only 1 night.
        // If stay is Mar 1-2 (1 night), item date is Mar 1. Arrival Mar 1, Departure Mar 2.
        // So Departure today means max(date) was yesterday.

        $stats = [
            'total_bookings' => Booking::count(),
            'confirmed_today' => Booking::where('status', 'CONFIRMED')->count(),
            'pending_arrivals' => $arrivals->count(),
            'pending_departures' => $departures->count(),
        ];

        return [
            'arrivals' => $arrivals,
            'departures' => $departures,
            'stats' => $stats,
        ];
    }
}; ?>

<div class="p-6 lg:p-8 space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Admin Dashboard</h1>
        <p class="text-zinc-500 dark:text-zinc-400">Overview of today's hotel operations.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <flux:card class="p-6">
            <h3 class="text-sm font-medium text-zinc-500 uppercase tracking-wider">Total Bookings</h3>
            <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white">
                {{ number_format($stats['total_bookings']) }}</p>
        </flux:card>

        <flux:card class="p-6">
            <h3 class="text-sm font-medium text-zinc-500 uppercase tracking-wider">Confirmed</h3>
            <p class="mt-2 text-3xl font-black text-green-600">{{ number_format($stats['confirmed_today']) }}</p>
        </flux:card>

        <flux:card class="p-6">
            <h3 class="text-sm font-medium text-zinc-500 uppercase tracking-wider">Arrivals Today</h3>
            <p class="mt-2 text-3xl font-black text-blue-600">{{ number_format($stats['pending_arrivals']) }}</p>
        </flux:card>

        <flux:card class="p-6">
            <h3 class="text-sm font-medium text-zinc-500 uppercase tracking-wider">Departures Today</h3>
            <p class="mt-2 text-3xl font-black text-orange-600">{{ number_format($stats['pending_departures']) }}</p>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Arrivals -->
        <flux:card class="p-0 overflow-hidden">
            <div
                class="p-6 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center bg-zinc-50/50 dark:bg-zinc-800/30">
                <h2 class="font-bold flex items-center gap-2">
                    <flux:icon.arrow-down-left class="w-5 h-5 text-blue-500" />
                    Today's Arrivals
                </h2>
                <flux:badge color="blue" inset="top">{{ $arrivals->count() }}</flux:badge>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-100 dark:border-zinc-800">
                            <th class="px-6 py-4">Guest</th>
                            <th class="px-6 py-4">Room Type</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($arrivals as $booking)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ $booking->customer_name }}
                                    </div>
                                    <div class="text-xs text-zinc-500">#{{ $booking->id }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    {{ $booking->items->first()->roomType->name }}
                                </td>
                                <td class="px-6 py-4">
                                    <flux:badge size="sm"
                                        :color="$booking->status === 'CONFIRMED' ? 'green' : 'zinc'">
                                        {{ $booking->status }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <flux:button variant="ghost" size="sm">Manage</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-zinc-500">
                                    No arrivals scheduled for today.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>

        <!-- Departures -->
        <flux:card class="p-0 overflow-hidden">
            <div
                class="p-6 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center bg-zinc-50/50 dark:bg-zinc-800/30">
                <h2 class="font-bold flex items-center gap-2">
                    <flux:icon.arrow-up-right class="w-5 h-5 text-orange-500" />
                    Today's Departures
                </h2>
                <flux:badge color="orange" inset="top">{{ $departures->count() }}</flux:badge>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="text-xs font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-100 dark:border-zinc-800">
                            <th class="px-6 py-4">Guest</th>
                            <th class="px-6 py-4">Room Type</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($departures as $booking)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ $booking->customer_name }}
                                    </div>
                                    <div class="text-xs text-zinc-500">#{{ $booking->id }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    {{ $booking->items->first()->roomType->name }}
                                </td>
                                <td class="px-6 py-4">
                                    <flux:badge size="sm" color="zinc">
                                        {{ $booking->status }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <flux:button variant="ghost" size="sm">Manage</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-zinc-500">
                                    No departures scheduled for today.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
    </div>
</div>
