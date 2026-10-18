<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'user_id',
        'event_package_id',
        'event_date',
        'event_location',
        'participants',
        'customer_notes',
        'base_price_snapshot',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'participants' => 'integer',
            'base_price_snapshot' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventPackage(): BelongsTo
    {
        return $this->belongsTo(EventPackage::class);
    }

    public function priceRecommendations(): HasMany
    {
        return $this->hasMany(PriceRecommendation::class);
    }
    public function priceQuote(): HasOne
    {
        return $this->hasOne(PriceQuote::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
