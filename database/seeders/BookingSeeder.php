<?php

namespace Database\Seeders;

use App\Models\Availability;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Superior Studio',
                'description' => 'A cozy 24sqm studio with a queen bed and modern amenities. Perfect for solo travelers or couples.',
                'capacity' => 2,
                'base_price' => 1200,
                'rooms' => 5
            ],
            [
                'name' => 'Deluxe Garden View',
                'description' => 'A spacious 32sqm room featuring a private balcony overlooking our lush tropical gardens.',
                'capacity' => 2,
                'base_price' => 1800,
                'rooms' => 8
            ],
            [
                'name' => 'Family Suite',
                'description' => 'Two interconnected rooms with a living area, king bed, and twin beds. Ideal for families up to 4.',
                'capacity' => 4,
                'base_price' => 3500,
                'rooms' => 3
            ],
        ];

        foreach ($types as $typeData) {
            $roomCount = $typeData['rooms'];
            unset($typeData['rooms']);

            $roomType = RoomType::create($typeData);

            // Create Rooms
            for ($i = 1; $i <= $roomCount; $i++) {
                Room::create([
                    'room_type_id' => $roomType->id,
                    'room_number' => strtoupper(substr($roomType->name, 0, 1)) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'status' => 'available',
                ]);
            }

            // Create Availability for 60 days
            $period = CarbonPeriod::create(now()->subDays(2), now()->addDays(58));
            foreach ($period as $date) {
                Availability::create([
                    'room_type_id' => $roomType->id,
                    'date' => $date->format('Y-m-d'),
                    'total_inventory' => $roomCount,
                    'booked_count' => 0,
                    'blocked_count' => 0,
                ]);
            }
        }
    }
}
