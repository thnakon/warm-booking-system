<?php

use Illuminate\Support\Facades\Route;

use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Volt::route('/search', 'booking.search')->name('booking.search');
Volt::route('/checkout', 'booking.checkout')->name('booking.checkout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Volt::route('admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
    Volt::route('admin/bookings', 'admin.bookings.index')->name('admin.bookings.index');
    Volt::route('admin/bookings/create', 'admin.bookings.create')->name('admin.bookings.create');
    Volt::route('admin/bookings/{booking}', 'admin.bookings.show')->name('admin.bookings.show');
    Volt::route('admin/tape-chart', 'admin.tape-chart')->name('admin.tape-chart');
});

require __DIR__ . '/settings.php';
