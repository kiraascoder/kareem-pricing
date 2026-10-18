<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceQuote extends Model
{
    protected $fillable = [
        'booking_id',
        'admin_id',
        'estimated_price',
        'final_price',
        'admin_notes',
        'customer_notes',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_price' => 'decimal:2',
            'final_price' => 'decimal:2',
            'responded_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
