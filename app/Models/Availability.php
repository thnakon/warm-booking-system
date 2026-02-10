<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'date',
        'price',
        'total_inventory',
        'booked_count',
        'blocked_count',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'total_inventory' => 'integer',
        'booked_count' => 'integer',
        'blocked_count' => 'integer',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
