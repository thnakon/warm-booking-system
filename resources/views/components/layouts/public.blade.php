<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Warm Resort') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:400,700"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>

<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 font-sans antialiased text-zinc-900 dark:text-zinc-100">
    <flux:header
        class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md sticky top-0 z-50 border-b border-zinc-100 dark:border-zinc-800">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between w-full">
            <a href="{{ route('home') }}" class="flex items-center gap-2 group" wire:navigate>
                <div
                    class="size-9 bg-zinc-900 dark:bg-zinc-100 flex items-center justify-center rounded-lg group-hover:scale-105 transition-transform">
                    <flux:icon.sun class="size-6 text-white dark:text-zinc-900" />
                </div>
                <span class="text-xl font-bold tracking-tight font-serif">WARM <span
                        class="text-zinc-500">RESORT</span></span>
            </a>

            <div class="flex items-center gap-10">
                <flux:navbar class="gap-8 max-md:hidden">
                    <flux:navbar.item :href="route('home')" :current="request()->routeIs('home')" wire:navigate>Home
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('booking.search')" :current="request()->routeIs('booking.search')"
                        wire:navigate>Rooms</flux:navbar.item>
                    <flux:navbar.item href="#">Gallery</flux:navbar.item>
                    <flux:navbar.item href="#">About</flux:navbar.item>
                </flux:navbar>

                <div class="flex items-center gap-4 border-l border-zinc-100 dark:border-zinc-800 pl-8">
                    @auth
                        <flux:button variant="ghost" :href="route('dashboard')" wire:navigate>Merchant Admin</flux:button>
                    @else
                        <flux:button variant="ghost" :href="route('login')" wire:navigate
                            class="text-zinc-500 hover:text-zinc-900 dark:hover:text-white">Merchant Log in</flux:button>
                    @endauth
                    <flux:button variant="primary" :href="route('booking.search')" wire:navigate
                        class="max-sm:hidden tracking-widest uppercase text-[10px] font-bold px-6">Book
                        Now</flux:button>
                    <flux:sidebar.toggle class="md:hidden" icon="bars-2" />
                </div>
            </div>
        </div>
    </flux:header>

    <main>
        {{ $slot }}
    </main>

    <footer class="bg-zinc-900 text-zinc-400 py-16 mt-20">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-12">
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center gap-2 mb-6">
                    <div class="size-8 bg-white flex items-center justify-center rounded-lg">
                        <flux:icon.sun class="size-5 text-zinc-900" />
                    </div>
                    <span class="text-white text-xl font-bold tracking-tight font-serif">WARM RESORT</span>
                </div>
                <p class="max-w-xs leading-relaxed">
                    A boutique sanctuary in the heart of Thailand, where modern luxury meets tropical tranquility.
                </p>
            </div>
            <div>
                <h4 class="text-white font-bold mb-6">Quick Links</h4>
                <ul class="space-y-4 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a></li>
                    <li><a href="{{ route('booking.search') }}"
                            class="hover:text-white transition-colors">Reservations</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Room Types</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold mb-6">Contact Us</h4>
                <ul class="space-y-4 text-sm">
                    <li class="flex items-start gap-3">
                        <flux:icon.map-pin class="size-5 shrink-0" />
                        <span>123 Tropical Beach Rd, Koh Samui, Thailand</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <flux:icon.phone class="size-5 shrink-0" />
                        <span>+66 81 234 5678</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-6 mt-16 pt-8 border-t border-zinc-800 text-sm text-center">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </footer>

    @fluxScripts
</body>

</html>
