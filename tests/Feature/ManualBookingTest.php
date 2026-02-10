<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ManualBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_manual_booking(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create(['base_price' => 2000]);

        // Setup availability for today and tomorrow
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        Availability::create(['room_type_id' => $roomType->id, 'date' => $today, 'total_inventory' => 5]);
        Availability::create(['room_type_id' => $roomType->id, 'date' => $tomorrow, 'total_inventory' => 5]);

        $this->actingAs($user);

        Volt::test('admin.bookings.create')
            ->set('checkIn', $today)
            ->set('checkOut', $tomorrow)
            ->set('roomTypeId', $roomType->id)
            ->set('name', 'Manual Guest')
            ->set('email', 'manual@example.com')
            ->set('phone', '0812345678')
            ->set('status', 'CONFIRMED')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'customer_name' => 'Manual Guest',
            'status' => 'CONFIRMED'
        ]);

        $this->assertDatabaseHas('booking_items', [
            'room_type_id' => $roomType->id,
            'date' => $today . ' 00:00:00'
        ]);
    }

    public function test_cannot_create_manual_booking_if_no_availability(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create();

        // No availability created for the dates
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->actingAs($user);

        Volt::test('admin.bookings.create')
            ->set('checkIn', $today)
            ->set('checkOut', $tomorrow)
            ->set('roomTypeId', $roomType->id)
            ->set('name', 'Manual Guest')
            ->set('email', 'manual@example.com')
            ->set('phone', '0812345678')
            ->call('save')
            ->assertHasErrors(['booking']);
    }
}
