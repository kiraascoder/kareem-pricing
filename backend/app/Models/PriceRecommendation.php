<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRecommendation extends Model
{
    protected $fillable = [
        'booking_id',
        'base_price',
        'predicted_demand',
        'fuzzy_adjustment_percent',
        'recommended_price',
        'model_version',
        'calculation_method',
        'input_features',
        'calculation_details',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'predicted_demand' => 'decimal:4',
            'fuzzy_adjustment_percent' => 'decimal:4',
            'recommended_price' => 'decimal:2',
            'input_features' => 'array',
            'calculation_details' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
