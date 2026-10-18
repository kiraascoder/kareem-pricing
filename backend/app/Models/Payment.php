<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'price_quote_id',
        'verified_by',
        'amount',
        'payment_method',
        'sender_name',
        'bank_name',
        'transferred_at',
        'proof_path',
        'status',
        'customer_notes',
        'admin_notes',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transferred_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'proof_path',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function priceQuote(): BelongsTo
    {
        return $this->belongsTo(PriceQuote::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
