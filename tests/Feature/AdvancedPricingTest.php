<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_calculates_total_price_with_dynamic_rates(): void
    {
        // 1. Setup
        $user = User::factory()->create();
        $this->actingAs($user);

        $roomType = RoomType::factory()->create(['base_price' => 1000]);
        $checkIn = now()->format('Y-m-d');
        $checkOut = now()->addDays(2)->format('Y-m-d'); // 2 nights

        // 2. Create Availability with overrides
        // Night 1: Standard Base Price (no override in DB, or explicit null)
        Availability::create([
            'room_type_id' => $roomType->id,
            'date' => $checkIn,
            'total_inventory' => 5,
            'price' => null // Should use base_price 1000
        ]);

        // Night 2: High Season Price
        Availability::create([
            'room_type_id' => $roomType->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'total_inventory' => 5,
            'price' => 2500 // Override
        ]);

        // 3. Execute Service
        $service = new BookingService();
        $booking = $service->createBooking(
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '1234567890'
            ],
            $roomType->id,
            $checkIn,
            $checkOut
        );

        // 4. Assert
        // Expected: 1000 + 2500 = 3500
        $this->assertEquals(3500, $booking->total_price);

        // Assert Items
        $this->assertDatabaseHas('booking_items', [
            'booking_id' => $booking->id,
            'price' => 1000,
            'date' => $checkIn . ' 00:00:00'
        ]);

        $this->assertDatabaseHas('booking_items', [
            'booking_id' => $booking->id,
            'price' => 2500,
            'date' => now()->addDay()->format('Y-m-d') . ' 00:00:00'
        ]);
    }

    public function test_search_results_show_dynamic_total_price(): void
    {
        // 1. Setup
        $roomType = RoomType::factory()->create(['base_price' => 1000]);
        $checkIn = now()->format('Y-m-d');
        $checkOut = now()->addDays(2)->format('Y-m-d'); // 2 nights

        Availability::create([
            'room_type_id' => $roomType->id,
            'date' => $checkIn,
            'total_inventory' => 1,
            'price' => 1000
        ]);

        Availability::create([
            'room_type_id' => $roomType->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'total_inventory' => 1,
            'price' => 2000
        ]);

        // 2. Execute Service
        $service = new BookingService();
        $results = $service->getAvailableRoomTypes($checkIn, $checkOut);

        // 3. Assert
        $this->assertCount(1, $results);
        $this->assertEquals(3000, $results->first()->total_price);
    }
}
