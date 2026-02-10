<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Models\Availability;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $roomType = RoomType::first();
        if (!$roomType) return;

        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        // 1. Arrival Today (Starts today, ends tomorrow)
        $booking1 = Booking::create([
            'customer_name' => 'Arrival Guest',
            'customer_email' => 'arrival@example.com',
            'customer_phone' => '111',
            'total_price' => $roomType->base_price,
            'status' => 'CONFIRMED',
        ]);
        BookingItem::create([
            'booking_id' => $booking1->id,
            'room_type_id' => $roomType->id,
            'date' => $today,
            'price' => $roomType->base_price,
        ]);

        // 2. Departure Today (Started yesterday, ends today)
        $booking2 = Booking::create([
            'customer_name' => 'Departure Guest',
            'customer_email' => 'departure@example.com',
            'customer_phone' => '222',
            'total_price' => $roomType->base_price,
            'status' => 'CONFIRMED',
        ]);
        BookingItem::create([
            'booking_id' => $booking2->id,
            'room_type_id' => $roomType->id,
            'date' => $yesterday,
            'price' => $roomType->base_price,
        ]);

        // 3. Staying (Started yesterday, ends tomorrow)
        $booking3 = Booking::create([
            'customer_name' => 'Staying Guest',
            'customer_email' => 'staying@example.com',
            'customer_phone' => '333',
            'total_price' => $roomType->base_price * 2,
            'status' => 'CONFIRMED',
        ]);
        BookingItem::create([
            'booking_id' => $booking3->id,
            'room_type_id' => $roomType->id,
            'date' => $yesterday,
            'price' => $roomType->base_price,
        ]);
        BookingItem::create([
            'booking_id' => $booking3->id,
            'room_type_id' => $roomType->id,
            'date' => $today,
            'price' => $roomType->base_price,
        ]);
    }
}
