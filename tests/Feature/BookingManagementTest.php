<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_booking_list(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create();
        $booking = Booking::factory()->create(['customer_name' => 'John Doe']);
        BookingItem::create(['booking_id' => $booking->id, 'room_type_id' => $roomType->id, 'date' => now(), 'price' => 1000]);

        $this->actingAs($user);

        $response = $this->get(route('admin.bookings.index'));
        $response->assertStatus(200);
        $response->assertSee('John Doe');
    }

    public function test_can_search_bookings(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create();

        $booking1 = Booking::factory()->create(['customer_name' => 'Alice Smith']);
        BookingItem::create(['booking_id' => $booking1->id, 'room_type_id' => $roomType->id, 'date' => now(), 'price' => 1000]);

        $booking2 = Booking::factory()->create(['customer_name' => 'Bob Jones']);
        BookingItem::create(['booking_id' => $booking2->id, 'room_type_id' => $roomType->id, 'date' => now(), 'price' => 1000]);

        $this->actingAs($user);

        Volt::test('admin.bookings.index')
            ->set('search', 'Alice')
            ->assertSee('Alice Smith')
            ->assertDontSee('Bob Jones');
    }

    public function test_can_filter_bookings_by_status(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create();

        $booking1 = Booking::factory()->create(['customer_name' => 'Hold Guest', 'status' => 'HOLD']);
        BookingItem::create(['booking_id' => $booking1->id, 'room_type_id' => $roomType->id, 'date' => now(), 'price' => 1000]);

        $booking2 = Booking::factory()->create(['customer_name' => 'Confirmed Guest', 'status' => 'CONFIRMED']);
        BookingItem::create(['booking_id' => $booking2->id, 'room_type_id' => $roomType->id, 'date' => now(), 'price' => 1000]);

        $this->actingAs($user);

        Volt::test('admin.bookings.index')
            ->set('status', 'CONFIRMED')
            ->assertSee('Confirmed Guest')
            ->assertDontSee('Hold Guest');
    }

    public function test_can_update_booking_status(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create();
        $booking = Booking::factory()->create(['status' => 'HOLD']);
        BookingItem::create(['booking_id' => $booking->id, 'room_type_id' => $roomType->id, 'date' => now(), 'price' => 1000]);

        $this->actingAs($user);

        Volt::test('admin.bookings.show', ['booking' => $booking])
            ->set('status', 'CONFIRMED')
            ->call('updateStatus')
            ->assertHasNoErrors();

        $this->assertEquals('CONFIRMED', $booking->fresh()->status);
    }

    public function test_can_assign_room_to_item(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create(['room_type_id' => $roomType->id, 'room_number' => '101']);

        $booking = Booking::factory()->create();
        $item = BookingItem::create([
            'booking_id' => $booking->id,
            'room_type_id' => $roomType->id,
            'date' => now()->format('Y-m-d'),
            'price' => 1000
        ]);

        $this->actingAs($user);

        Volt::test('admin.bookings.show', ['booking' => $booking])
            ->call('assignRoom', $item->id, $room->id)
            ->assertHasNoErrors();

        $this->assertEquals($room->id, $item->fresh()->room_id);
    }
}
