<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Availability;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BookingSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_correctly(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/search');

        $response->assertStatus(200);
        $response->assertSee('Find Your Perfect Room');
    }

    public function test_can_search_and_see_available_rooms(): void
    {
        $roomType = RoomType::factory()->create([
            'name' => 'Superior Studio',
            'base_price' => 1000,
        ]);

        // Create availability for 2 days
        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => now()->format('Y-m-d'),
            'total_inventory' => 5,
            'booked_count' => 0,
        ]);
        Availability::factory()->create([
            'room_type_id' => $roomType->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'total_inventory' => 5,
            'booked_count' => 0,
        ]);

        Volt::test('booking.search')
            ->set('checkIn', now()->format('Y-m-d'))
            ->set('checkOut', now()->addDays(2)->format('Y-m-d'))
            ->call('executeSearch')
            ->assertSee('Superior Studio')
            ->assertSee('฿2,000');
    }

    public function test_shows_no_availability_message(): void
    {
        $roomType = RoomType::factory()->create([
            'name' => 'Superior Studio',
        ]);

        // No availability created in database

        Volt::test('booking.search')
            ->set('checkIn', now()->format('Y-m-d'))
            ->set('checkOut', now()->addDays(1)->format('Y-m-d'))
            ->call('executeSearch')
            ->assertSee('No availability for these dates');
    }
}
