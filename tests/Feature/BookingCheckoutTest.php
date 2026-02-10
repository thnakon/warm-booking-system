<?php

namespace Tests\Feature;

use App\Models\Availability;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BookingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_checkout_page(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create();

        $response = $this->actingAs($user)->get(route('booking.checkout', [
            'roomTypeId' => $roomType->id,
            'checkIn' => now()->format('Y-m-d'),
            'checkOut' => now()->addDay()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertSee($roomType->name);
        $response->assertSee('Reservation Summary');
    }

    public function test_can_confirm_booking(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create(['base_price' => 1000]);

        $checkIn = now()->format('Y-m-d');
        $checkOut = now()->addDays(2)->format('Y-m-d');

        // Setup availability
        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => $checkIn,
            'total_inventory' => 5,
            'booked_count' => 0,
        ]);
        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'total_inventory' => 5,
            'booked_count' => 0,
        ]);

        $this->actingAs($user);

        Volt::test('booking.checkout', [
            'roomTypeId' => $roomType->id,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
        ])
            ->set('name', 'John Doe')
            ->set('email', 'john@example.com')
            ->set('phone', '0812345678')
            ->call('confirmBooking')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('bookings', [
            'customer_name' => 'John Doe',
            'total_price' => 2000,
            'status' => 'HOLD',
        ]);

        $this->assertEquals(1, Availability::whereDate('date', $checkIn)->first()->booked_count);
    }

    public function test_validation_works(): void
    {
        $roomType = RoomType::factory()->create();

        Volt::test('booking.checkout', [
            'roomTypeId' => $roomType->id,
            'checkIn' => now()->format('Y-m-d'),
            'checkOut' => now()->addDay()->format('Y-m-d'),
        ])
            ->set('name', '')
            ->set('email', 'not-an-email')
            ->call('confirmBooking')
            ->assertHasErrors(['name', 'email', 'phone']);
    }
}
