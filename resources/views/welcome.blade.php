<x-layouts.public>
    {{-- Hero Section --}}
    <section class="relative min-h-[90vh] flex items-center pt-20">
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/hero.png') }}" alt="Warm Resort Hero" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-900/80 via-zinc-900/40 to-transparent"></div>
        </div>

        <div class="max-w-7xl mx-auto px-6 relative z-10 w-full">
            <div class="max-w-2xl">
                <flux:badge color="yellow" variant="outline" class="mb-6 uppercase tracking-widest text-xs font-bold">
                    Welcome to Paradise</flux:badge>
                <h1 class="text-5xl md:text-7xl font-serif font-bold text-white leading-tight mb-6">
                    Escape to a <br><span class="text-yellow-400 italic">Warm</span> Sanctuary
                </h1>
                <p class="text-xl text-zinc-200 mb-10 leading-relaxed max-w-lg">
                    Experience the perfect blend of modern luxury and authentic Thai hospitality at the heart of Samui's
                    most pristine coast.
                </p>

                <div class="flex flex-wrap gap-4">
                    <flux:button variant="primary" :href="route('booking.search')" wire:navigate
                        class="h-14 px-8 text-lg">
                        Book Your Stay
                    </flux:button>
                    <flux:button variant="ghost" icon="play-circle"
                        class="h-14 px-8 text-lg text-white hover:bg-white/10">
                        View Video
                    </flux:button>
                </div>
            </div>
        </div>

        {{-- Floating Booking Widget --}}
        <div class="absolute -bottom-12 left-0 right-0 z-20 max-md:hidden px-6">
            <div
                class="max-w-5xl mx-auto bg-white dark:bg-zinc-900 p-8 rounded-2xl shadow-2xl border border-zinc-100 dark:border-zinc-800">
                <form action="{{ route('booking.search') }}" method="GET" class="grid grid-cols-4 gap-6 items-end">
                    <flux:input label="Check-in Date" type="date" name="checkIn"
                        value="{{ now()->format('Y-m-d') }}" />
                    <flux:input label="Check-out Date" type="date" name="checkOut"
                        value="{{ now()->addDays(2)->format('Y-m-d') }}" />
                    <flux:select label="Guests">
                        <flux:select.option value="1">1 Adult</flux:select.option>
                        <flux:select.option value="2" selected>2 Adults</flux:select.option>
                        <flux:select.option value="3">3 Adults</flux:select.option>
                        <flux:select.option value="4">Family (4+)</flux:select.option>
                    </flux:select>
                    <flux:button variant="primary" type="submit" class="h-10">SEARCH AVAILABILITY</flux:button>
                </form>
            </div>
        </div>
    </section>

    {{-- Room Sections --}}
    <section class="max-w-7xl mx-auto px-6 mt-32 md:mt-48 py-16">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-serif font-bold mb-4">Exceptional Accommodations</h2>
            <p class="text-zinc-500 max-w-xl mx-auto">Each of our rooms is designed to be a personal sanctuary,
                featuring bespoke furnishings and panoramic views.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            {{-- Luxury Suite --}}
            <div class="group cursor-pointer">
                <div class="overflow-hidden rounded-2xl mb-6 relative aspect-[4/3]">
                    <img src="{{ asset('images/room_luxury.png') }}" alt="Luxury Suite"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute bottom-4 left-4">
                        <flux:badge color="zinc" class="bg-black/50 backdrop-blur text-white border-0">Starting from
                            ฿4,500/night</flux:badge>
                    </div>
                </div>
                <h3 class="text-2xl font-serif font-bold mb-2">Luxury Oceanfront Suite</h3>
                <p class="text-zinc-500 mb-4 leading-relaxed line-clamp-2">Our flagship suite featuring a direct view of
                    the Gulf of Thailand, private balcony, and custom teak furnishings.</p>
                <flux:button variant="ghost" :href="route('booking.search')" wire:navigate icon-trailing="arrow-right">
                    View details</flux:button>
            </div>

            {{-- Deluxe Room --}}
            <div class="group cursor-pointer">
                <div class="overflow-hidden rounded-2xl mb-6 relative aspect-[4/3]">
                    <img src="{{ asset('images/room_deluxe.png') }}" alt="Deluxe Garden Room"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute bottom-4 left-4">
                        <flux:badge color="zinc" class="bg-black/50 backdrop-blur text-white border-0">Starting from
                            ฿2,800/night</flux:badge>
                    </div>
                </div>
                <h3 class="text-2xl font-serif font-bold mb-2">Deluxe Garden Sanctuary</h3>
                <p class="text-zinc-500 mb-4 leading-relaxed line-clamp-2">A serene escape surrounded by lush tropical
                    gardens, featuring high ceilings and an open-concept bathroom.</p>
                <flux:button variant="ghost" :href="route('booking.search')" wire:navigate icon-trailing="arrow-right">
                    View details</flux:button>
            </div>
        </div>
    </section>

    {{-- Amenities Section --}}
    <section class="bg-zinc-100 dark:bg-zinc-900 py-24 my-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                <div class="flex flex-col items-center text-center">
                    <div
                        class="size-16 bg-white dark:bg-zinc-800 rounded-full flex items-center justify-center mb-6 shadow-sm">
                        <flux:icon.sun class="size-8 text-yellow-500" />
                    </div>
                    <h4 class="font-bold mb-2">Infinite Sun</h4>
                    <p class="text-sm text-zinc-500">Private beach with exclusive service</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div
                        class="size-16 bg-white dark:bg-zinc-800 rounded-full flex items-center justify-center mb-6 shadow-sm">
                        <flux:icon.sparkles class="size-8 text-blue-500" />
                    </div>
                    <h4 class="font-bold mb-2">Premium Spa</h4>
                    <p class="text-sm text-zinc-500">Authentic Thai wellness treatments</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div
                        class="size-16 bg-white dark:bg-zinc-800 rounded-full flex items-center justify-center mb-6 shadow-sm">
                        <flux:icon.shopping-bag class="size-8 text-purple-500" />
                    </div>
                    <h4 class="font-bold mb-2">Gourmet Dining</h4>
                    <p class="text-sm text-zinc-500">Award-winning beachfront restaurant</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div
                        class="size-16 bg-white dark:bg-zinc-800 rounded-full flex items-center justify-center mb-6 shadow-sm">
                        <flux:icon.link class="size-8 text-green-500" />
                    </div>
                    <h4 class="font-bold mb-2">Smart Stays</h4>
                    <p class="text-sm text-zinc-500">High-speed fiber & automated service</p>
                </div>
            </div>
        </div>
    </section>

</x-layouts.public>
