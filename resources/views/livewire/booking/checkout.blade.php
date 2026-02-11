<?php

use Livewire\Volt\Component;
use App\Models\RoomType;
use App\Services\BookingService;
use Carbon\Carbon;
use Livewire\Attributes\Url;

use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    #[Url]
    public $roomTypeId;
    #[Url]
    public $checkIn;
    #[Url]
    public $checkOut;

    public $name;
    public $email;
    public $phone;
    public $slip;
    public $extraGuests = 0;
    public $paymentMethod = 'pay_at_hotel';

    public $roomType;
    public $nights;
    public $totalPrice;

    public function mount()
    {
        $this->roomType = RoomType::findOrFail($this->roomTypeId);
        $this->nights = Carbon::parse($this->checkIn)->diffInDays(Carbon::parse($this->checkOut));
        $this->totalPrice = $this->roomType->base_price * $this->nights;

        if (auth()->check()) {
            $this->name = auth()->user()->name;
            $this->email = auth()->user()->email;
        }
    }

    public function confirmBooking(BookingService $service)
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'slip' => 'nullable|image|max:10240', // 10MB
            'extraGuests' => 'nullable|integer|min:0',
        ]);

        try {
            $slipPath = null;
            if ($this->slip) {
                // Store in 'public' disk -> storage/app/public/payment_slips
                $slipPath = $this->slip->store('payment_slips', 'public');
            }

            $booking = $service->createBooking(
                [
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'payment_method' => $this->paymentMethod,
                    'slip_path' => $slipPath,
                    'extra_guests' => $this->extraGuests,
                ],
                $this->roomTypeId,
                $this->checkIn,
                $this->checkOut,
            );

            // In Phase 1, we just show success.
            // Later we might redirect to payment or dashboard.
            session()->flash('success', 'Booking confirmed! Your booking ID is #' . $booking->id);
            return $this->redirect(route('home'), navigate: true);
        } catch (\Exception $e) {
            $this->addError('booking', $e->getMessage());
        }
    }
}; ?>

<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">Complete Your Booking</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Review your stay details and provide your contact information.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
        <!-- Booking Details -->
        <div class="lg:col-span-3 space-y-8">
            <div
                class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <flux:icon.user class="w-5 h-5 text-zinc-400" />
                        Guest Information
                    </h2>
                </div>

                <div class="p-8 space-y-6">
                    @error('booking')
                        <div
                            class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 rounded-lg text-sm text-red-600 dark:text-red-400">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:input label="Full Name" wire:model="name" placeholder="e.g. John Doe" />
                        <flux:input label="Email Address" type="email" wire:model="email"
                            placeholder="john@example.com" />
                    </div>

                    <flux:input label="Phone Number" wire:model="phone" placeholder="08X-XXX-XXXX" />

                    <flux:separator />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <flux:input label="Extra Guests" type="number" wire:model="extraGuests" min="0" />
                    </div>

                    <flux:radio.group label="Payment Method" wire:model.live="paymentMethod">
                        <flux:radio value="pay_at_hotel" label="Pay at Hotel" />
                        <flux:radio value="bank_transfer" label="Bank Transfer (Upload Slip)" />
                    </flux:radio.group>

                    @if ($paymentMethod === 'bank_transfer')
                        <flux:input label="Payment Slip" type="file" wire:model="slip" />
                    @endif

                    <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:button variant="primary" wire:click="confirmBooking"
                            class="w-full h-12 font-bold text-lg">
                            Confirm & Reserve Now
                        </flux:button>
                        <p class="mt-4 text-center text-xs text-zinc-500 uppercase tracking-widest font-semibold">
                            Atomic Transaction & Instant Confirmation
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-2 space-y-6">
            <div
                class="bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-2xl shadow-xl p-8 sticky top-8">
                <h3 class="text-xl font-bold mb-6">Reservation Summary</h3>

                <div class="space-y-6">
                    <div>
                        <label
                            class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest block mb-1">Room
                            Selected</label>
                        <div class="text-lg font-semibold">{{ $roomType->name }}</div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label
                                class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest block mb-1">Check-in</label>
                            <div class="font-medium">{{ Carbon::parse($checkIn)->format('d M Y') }}</div>
                        </div>
                        <div class="flex-1">
                            <label
                                class="text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest block mb-1">Check-out</label>
                            <div class="font-medium">{{ Carbon::parse($checkOut)->format('d M Y') }}</div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-zinc-800 dark:border-zinc-200 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-400 dark:text-zinc-500">฿{{ number_format($roomType->base_price) }} x
                                {{ $nights }} Nights</span>
                            <span class="font-medium">฿{{ number_format($totalPrice) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-400 dark:text-zinc-500">Taxes & Fees</span>
                            <span class="font-medium italic">Included</span>
                        </div>

                        <div class="flex justify-between items-end pt-4">
                            <span class="text-sm font-bold uppercase tracking-widest text-zinc-400">Total Amount</span>
                            <span class="text-3xl font-black">฿{{ number_format($totalPrice) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-zinc-800 dark:border-zinc-200">
                    <flux:button href="{{ route('booking.search') }}" variant="ghost"
                        class="w-full text-zinc-400 hover:text-white" wire:navigate>
                        ← Back to dates
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
