<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_requires_auth(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_arrivals_and_departures(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create();

        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        // Arrival today
        $booking1 = Booking::create([
            'customer_name' => 'Arrival Guest',
            'customer_email' => 'a@b.com',
            'customer_phone' => '1',
            'total_price' => 1000,
            'status' => 'CONFIRMED'
        ]);
        BookingItem::create([
            'booking_id' => $booking1->id,
            'room_type_id' => $roomType->id,
            'date' => $today,
            'price' => 1000
        ]);

        // Departure today (Last stay night was yesterday)
        $booking2 = Booking::create([
            'customer_name' => 'Departure Guest',
            'customer_email' => 'd@b.com',
            'customer_phone' => '2',
            'total_price' => 1000,
            'status' => 'CONFIRMED'
        ]);
        BookingItem::create([
            'booking_id' => $booking2->id,
            'room_type_id' => $roomType->id,
            'date' => $yesterday,
            'price' => 1000
        ]);

        $this->actingAs($user);

        Volt::test('admin.dashboard')
            ->assertSee('Arrival Guest')
            ->assertSee('Departure Guest')
            ->assertSee('Arrivals Today')
            ->assertSee('Departures Today');
    }
}
