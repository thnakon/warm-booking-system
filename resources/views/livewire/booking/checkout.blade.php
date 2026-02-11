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

    public function rendering($view, $data)
    {
        $view->layout('components.layouts.public');
    }
}; ?>

<div class="max-w-7xl mx-auto py-16 px-6">
    <div class="mb-12 text-center">
        <flux:badge color="zinc" variant="outline" class="mb-4 uppercase tracking-widest text-xs font-bold">Step 2:
            Guest Details</flux:badge>
        <h1 class="text-4xl font-serif font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Confirm Your Stay</h1>
        <p class="mt-4 text-zinc-500 dark:text-zinc-400 max-w-xl mx-auto leading-relaxed">
            Please provide your details below to finalize your reservation.
            We'll send a confirmation email with your booking itinerary.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 items-start">
        <!-- Booking Details -->
        <div class="lg:col-span-7 space-y-12">
            <div
                class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-[2.5rem] shadow-2xl shadow-zinc-200/50 dark:shadow-none overflow-hidden">
                <div class="p-8 border-b border-zinc-50 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-800/20">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white flex items-center gap-3">
                        <div class="p-2 bg-zinc-900 dark:bg-zinc-100 rounded-lg text-white dark:text-zinc-900">
                            <flux:icon.user class="size-5" />
                        </div>
                        Guest Information
                    </h2>
                </div>

                <div class="p-10 space-y-8">
                    @error('booking')
                        <div
                            class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 rounded-2xl text-sm text-red-600 dark:text-red-400 flex gap-3 items-start">
                            <flux:icon.exclamation-circle class="size-5 shrink-0" />
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <flux:input label="Full Name" wire:model="name" placeholder="John Doe" class="h-12" />
                        <flux:input label="Email Address" type="email" wire:model="email"
                            placeholder="john@example.com" class="h-12" />
                    </div>

                    <flux:input label="Phone Number" wire:model="phone" placeholder="08X-XXX-XXXX" class="h-12" />

                    <flux:separator variant="subtle" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <flux:input label="Extra Guests (if any)" type="number" wire:model="extraGuests" min="0"
                            class="h-12" />
                        <div class="flex flex-col justify-end">
                            <p class="text-xs text-zinc-400 italic">Additional ฿500 per person per night</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Payment Option</label>
                        <flux:radio.group wire:model.live="paymentMethod" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div
                                class="relative flex items-start p-4 border border-zinc-100 dark:border-zinc-800 rounded-2xl cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <flux:radio value="pay_at_hotel" label="Pay at Hotel" />
                            </div>
                            <div
                                class="relative flex items-start p-4 border border-zinc-100 dark:border-zinc-800 rounded-2xl cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <flux:radio value="bank_transfer" label="Bank Transfer" />
                            </div>
                        </flux:radio.group>
                    </div>

                    @if ($paymentMethod === 'bank_transfer')
                        <div
                            class="bg-zinc-50 dark:bg-zinc-800/40 p-6 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm text-zinc-500 mb-4 font-medium">Please upload your transfer slip for
                                verification:</p>
                            <flux:input label="Payment Slip" type="file" wire:model="slip" />
                        </div>
                    @endif

                    <div class="pt-6">
                        <flux:button variant="primary" wire:click="confirmBooking"
                            class="w-full h-16 font-bold text-lg rounded-2xl tracking-widest uppercase">
                            CONFIRM & RESERVE NOW
                        </flux:button>
                        <div class="mt-6 flex items-center justify-center gap-2 text-zinc-400">
                            <flux:icon.shield-check class="size-4" />
                            <span class="text-xs uppercase tracking-tighter font-semibold">SECURE SSL
                                RESERVATION</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-5">
            <div
                class="bg-zinc-900 text-white rounded-[2.5rem] shadow-2xl p-10 lg:p-12 sticky top-32 overflow-hidden relative">
                {{-- Decorative pattern --}}
                <div class="absolute -top-12 -right-12 size-48 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-12 -left-12 size-48 bg-yellow-400/5 rounded-full blur-3xl"></div>

                <h3 class="text-2xl font-serif font-bold mb-10 pb-6 border-b border-white/10">Reservation Summary</h3>

                <div class="space-y-10 relative z-10">
                    <div>
                        <label class="text-xs font-bold text-zinc-500 uppercase tracking-widest block mb-2">Selected
                            Sanctuary</label>
                        <div class="text-xl font-bold text-white">{{ $roomType->name }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-8 p-6 bg-white/5 rounded-2xl">
                        <div>
                            <label
                                class="text-xs font-bold text-zinc-500 uppercase tracking-widest block mb-1">Check-in</label>
                            <div class="text-lg font-medium">{{ Carbon::parse($checkIn)->format('d M Y') }}</div>
                        </div>
                        <div>
                            <label
                                class="text-xs font-bold text-zinc-500 uppercase tracking-widest block mb-1">Check-out</label>
                            <div class="text-lg font-medium">{{ Carbon::parse($checkOut)->format('d M Y') }}</div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-zinc-400">
                            <span>฿{{ number_format($roomType->base_price) }} x {{ $nights }} Nights</span>
                            <span class="text-white font-medium">฿{{ number_format($totalPrice) }}</span>
                        </div>
                        @if ($extraGuests > 0)
                            <div class="flex justify-between items-center text-zinc-400">
                                <span>Extra Guests surcharge</span>
                                <span class="text-white font-medium italic">Included in Base</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center text-zinc-400">
                            <span>Service Charge & VAT</span>
                            <span class="text-white font-medium italic">Included</span>
                        </div>

                        <flux:separator variant="subtle" class="border-white/10" />

                        <div class="flex justify-between items-end pt-4">
                            <div class="text-sm font-bold uppercase tracking-widest text-zinc-500">Total payable</div>
                            <div class="text-4xl font-black text-white">฿{{ number_format($totalPrice) }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-12 pt-8 border-t border-white/10 relative z-10">
                    <flux:button href="{{ route('booking.search') }}" variant="ghost"
                        class="w-full text-zinc-500 hover:text-white hover:bg-white/5" wire:navigate>
                        ← Adjust dates or room
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
