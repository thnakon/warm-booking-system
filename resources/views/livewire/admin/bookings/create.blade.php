<?php

use Livewire\Volt\Component;
use App\Models\RoomType;
use App\Services\BookingService;
use Carbon\Carbon;

new class extends Component {
    public $checkIn;
    public $checkOut;
    public $roomTypeId;
    public $name;
    public $email;
    public $phone;
    public $status = 'CONFIRMED';

    public function mount()
    {
        $this->checkIn = now()->format('Y-m-d');
        $this->checkOut = now()->addDay()->format('Y-m-d');
    }

    public function save()
    {
        $this->validate([
            'checkIn' => 'required|date|after_or_equal:today',
            'checkOut' => 'required|date|after:checkIn',
            'roomTypeId' => 'required|exists:room_types,id',
            'name' => 'required|string|min:3',
            'email' => 'required|email',
            'phone' => 'required',
            'status' => 'required|in:HOLD,CONFIRMED',
        ]);

        $service = new BookingService();
        try {
            $booking = $service->createBooking(
                [
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone,
                ],
                (int) $this->roomTypeId,
                $this->checkIn,
                $this->checkOut,
            );

            $booking->update(['status' => $this->status]);

            session()->flash('success', 'Manual booking created successfully.');
            return $this->redirect(route('admin.bookings.show', $booking), navigate: true);
        } catch (\Exception $e) {
            $this->addError('booking', $e->getMessage());
        }
    }

    public function getRoomTypes()
    {
        return RoomType::all();
    }
}; ?>

<div class="max-w-4xl mx-auto py-10 px-4 space-y-8">
    <flux:breadcrumbs class="mb-6">
        <flux:breadcrumbs.item :href="route('dashboard')">Admin</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('admin.bookings.index')">Bookings</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>New Reservation</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div>
        <h1 class="text-3xl font-black text-zinc-900 dark:text-white">New Manual Reservation</h1>
        <p class="text-zinc-500 mt-1">Create a booking on behalf of a guest (Front Desk Entry).</p>
    </div>

    @error('booking')
        <div
            class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 rounded-xl text-sm text-red-600 dark:text-red-400">
            {{ $message }}
        </div>
    @enderror

    <flux:card class="p-8">
        <form wire:submit="save" class="space-y-8">
            <!-- Stay Details -->
            <section class="space-y-4">
                <h3
                    class="text-sm font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-100 dark:border-zinc-800 pb-2">
                    Stay Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <flux:field>
                        <flux:label>Check-In</flux:label>
                        <flux:input type="date" wire:model.live="checkIn" />
                        <flux:error name="checkIn" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Check-Out</flux:label>
                        <flux:input type="date" wire:model.live="checkOut" />
                        <flux:error name="checkOut" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Room Type</flux:label>
                        <flux:select wire:model="roomTypeId" placeholder="Select type...">
                            @foreach ($this->getRoomTypes() as $type)
                                <flux:select.option value="{{ $type->id }}">{{ $type->name }}
                                    (฿{{ number_format($type->base_price) }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="roomTypeId" />
                    </flux:field>
                </div>
            </section>

            <!-- Guest Details -->
            <section class="space-y-4">
                <h3
                    class="text-sm font-bold text-zinc-400 uppercase tracking-widest border-b border-zinc-100 dark:border-zinc-800 pb-2">
                    Guest Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label>Guest Name</flux:label>
                        <flux:input wire:model="name" placeholder="Full name" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Email Address</flux:label>
                        <flux:input type="email" wire:model="email" placeholder="email@example.com" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Phone Number</flux:label>
                        <flux:input wire:model="phone" placeholder="08x-xxx-xxxx" />
                        <flux:error name="phone" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Initial Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="CONFIRMED">Confirmed (Default for manual)</flux:select.option>
                            <flux:select.option value="HOLD">Hold (Pending Payment)</flux:select.option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                </div>
            </section>

            <div class="flex justify-end gap-3 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button :href="route('admin.bookings.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create Reservation</flux:button>
            </div>
        </form>
    </flux:card>
</div>
