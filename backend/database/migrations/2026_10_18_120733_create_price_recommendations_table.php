<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_recommendations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->restrictOnDelete();

            $table->decimal('base_price', 15, 2);

            $table->decimal('predicted_demand', 8, 4)
                ->nullable();

            $table->decimal('fuzzy_adjustment_percent', 8, 4)
                ->nullable();

            $table->decimal('recommended_price', 15, 2);

            $table->string('model_version', 100)
                ->nullable();

            $table->string('calculation_method', 50);

            $table->json('input_features')->nullable();

            $table->json('calculation_details')->nullable();

            $table->string('status', 30)
                ->default('generated');

            $table->timestamps();

            $table->index(['booking_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_recommendations');
    }
};
