<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class BookingService
{
    /**
     * Check if a room type is available for a given date range.
     */
    public function checkAvailability(int $roomTypeId, string $startDate, string $endDate): bool
    {
        $period = CarbonPeriod::create($startDate, Carbon::parse($endDate)->subDay());

        foreach ($period as $date) {
            $availability = Availability::where('room_type_id', $roomTypeId)
                ->whereDate('date', $date->format('Y-m-d'))
                ->first();

            if (!$availability || ($availability->total_inventory - $availability->booked_count - $availability->blocked_count) <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get available room types for a date range with their prices.
     */
    public function getAvailableRoomTypes(string $startDate, string $endDate): Collection
    {
        $roomTypes = RoomType::all();
        $availableRoomTypes = collect();
        $nights = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));

        if ($nights <= 0) {
            return $availableRoomTypes;
        }

        foreach ($roomTypes as $roomType) {
            if ($this->checkAvailability($roomType->id, $startDate, $endDate)) {
                $totalPrice = $roomType->base_price * $nights; // Simplified for Phase 1
                $roomType->total_price = $totalPrice;
                $availableRoomTypes->push($roomType);
            }
        }

        return $availableRoomTypes;
    }

    /**
     * Create a booking with atomic lock to prevent overbooking.
     * 
     * @throws Exception
     */
    public function createBooking(array $customerData, int $roomTypeId, string $startDate, string $endDate): Booking
    {
        return DB::transaction(function () use ($customerData, $roomTypeId, $startDate, $endDate) {
            $period = CarbonPeriod::create($startDate, Carbon::parse($endDate)->subDay());
            $nights = count($period);

            // 1. Lock availability rows for update
            $availabilities = [];
            foreach ($period as $date) {
                $availability = Availability::where('room_type_id', $roomTypeId)
                    ->whereDate('date', $date->format('Y-m-d'))
                    ->lockForUpdate()
                    ->first();

                if (!$availability) {
                    throw new Exception("Availability data missing for " . $date->format('Y-m-d'));
                }

                if (($availability->total_inventory - $availability->booked_count - $availability->blocked_count) <= 0) {
                    throw new Exception("Room no longer available for " . $date->format('Y-m-d'));
                }

                $availabilities[] = $availability;
            }

            // 2. Create the booking header
            $roomType = RoomType::find($roomTypeId);
            $totalPrice = $roomType->base_price * $nights;

            $booking = Booking::create([
                'user_id' => auth()->id(),
                'total_price' => $totalPrice,
                'status' => 'HOLD',
                'customer_name' => $customerData['name'],
                'customer_email' => $customerData['email'],
                'customer_phone' => $customerData['phone'],
            ]);

            // 3. Create booking items and update availability
            foreach ($availabilities as $availability) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'room_type_id' => $roomTypeId,
                    'date' => $availability->date,
                    'price' => $roomType->base_price,
                ]);

                $availability->increment('booked_count');
            }

            return $booking;
        });
    }
}
