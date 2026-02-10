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

class TapeChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_tape_chart_renders_rooms_and_assigned_bookings(): void
    {
        $user = User::factory()->create();
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create(['room_type_id' => $roomType->id, 'room_number' => '102']);

        $today = now()->format('Y-m-d');

        $booking = Booking::factory()->create(['customer_name' => 'Tape Guest']);
        BookingItem::create([
            'booking_id' => $booking->id,
            'room_type_id' => $roomType->id,
            'room_id' => $room->id,
            'date' => $today,
            'price' => 1000
        ]);

        $this->actingAs($user);

        Volt::test('admin.tape-chart')
            ->assertSee('102')
            ->assertSee('Tape Guest')
            ->assertSee($booking->id);
    }

    public function test_can_navigate_tape_chart_dates(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $today = now()->format('Y-m-d');
        $nextWeek = now()->addDays(7)->format('Y-m-d');

        Volt::test('admin.tape-chart')
            ->assertSet('startDate', $today)
            ->call('moveNext')
            ->assertSet('startDate', $nextWeek)
            ->call('movePrev')
            ->assertSet('startDate', $today);
    }
}
