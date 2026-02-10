<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'room_type_id',
        'price_adjustment',
        'adjustment_type',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
