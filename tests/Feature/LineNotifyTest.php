<?php

namespace Tests\Feature;

use App\Models\RoomType;
use App\Models\User;
use App\Services\BookingService;
use App\Services\LineNotifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LineNotifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_creation_triggers_line_notification(): void
    {
        // 1. Mock LineNotifyService
        $mockLineService = Mockery::mock(LineNotifyService::class);
        $mockLineService->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($message) {
                return str_contains($message, 'New Booking!') &&
                    str_contains($message, 'Total:');
            }))
            ->andReturn(true);

        $this->app->instance(LineNotifyService::class, $mockLineService);

        // 2. Setup Booking Data
        $user = User::factory()->create();
        $this->actingAs($user);
        $roomType = RoomType::factory()->create(['base_price' => 1000]);

        \App\Models\Availability::create([
            'room_type_id' => $roomType->id,
            'date' => now()->format('Y-m-d'),
            'total_inventory' => 5,
            'price' => 1000,
        ]);

        $service = new BookingService();
        $service->createBooking(
            [
                'name' => 'Test Guest',
                'email' => 'test@example.com',
                'phone' => '123456',
            ],
            $roomType->id,
            now()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );
    }
}
