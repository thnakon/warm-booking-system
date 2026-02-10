<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Exception;
use Carbon\Carbon;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
    }

    public function test_can_check_availability(): void
    {
        $roomType = RoomType::factory()->create();

        // Setup availability for 3 days
        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => '2026-03-01',
            'total_inventory' => 2,
            'booked_count' => 0,
        ]);
        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => '2026-03-02',
            'total_inventory' => 2,
            'booked_count' => 1,
        ]);
        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => '2026-03-03',
            'total_inventory' => 2,
            'booked_count' => 2, // Full
        ]);

        // Available range
        $this->assertTrue($this->bookingService->checkAvailability($roomType->id, '2026-03-01', '2026-03-03'));

        // Full range (including March 3rd)
        $this->assertFalse($this->bookingService->checkAvailability($roomType->id, '2026-03-02', '2026-03-04'));

        // Missing data range
        $this->assertFalse($this->bookingService->checkAvailability($roomType->id, '2026-03-05', '2026-03-06'));
    }

    public function test_can_create_booking_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $roomType = RoomType::factory()->create(['base_price' => 1000]);

        // Setup availability for 2 nights
        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => '2026-03-01',
            'total_inventory' => 5,
            'booked_count' => 0,
        ]);
        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => '2026-03-02',
            'total_inventory' => 5,
            'booked_count' => 0,
        ]);

        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0812345678',
        ];

        $booking = $this->bookingService->createBooking($customerData, $roomType->id, '2026-03-01', '2026-03-03');

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals(2000, $booking->total_price);
        $this->assertDatabaseHas('bookings', ['customer_name' => 'John Doe']);

        // Assert items exist and verify dates using Carbon for driver compatibility
        $this->assertCount(2, $booking->items);
        $this->assertEquals('2026-03-01', Carbon::parse($booking->items[0]->date)->format('Y-m-d'));
        $this->assertEquals('2026-03-02', Carbon::parse($booking->items[1]->date)->format('Y-m-d'));

        // Verify availability updated
        $this->assertEquals(1, Availability::whereDate('date', '2026-03-01')->first()->booked_count);
        $this->assertEquals(1, Availability::whereDate('date', '2026-03-02')->first()->booked_count);
    }

    public function test_cannot_create_booking_if_no_availability(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $roomType = RoomType::factory()->create();

        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => '2026-03-01',
            'total_inventory' => 1,
            'booked_count' => 1, // Full
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Room no longer available");

        $this->bookingService->createBooking(
            ['name' => 'Test', 'email' => 't@e.com', 'phone' => '123'],
            $roomType->id,
            '2026-03-01',
            '2026-03-02'
        );
    }
}
