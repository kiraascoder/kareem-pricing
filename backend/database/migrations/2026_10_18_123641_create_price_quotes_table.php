<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::create('price_quotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->restrictOnDelete();

            $table->foreignId('admin_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->decimal('estimated_price', 15, 2);
            $table->decimal('final_price', 15, 2);

            $table->text('admin_notes')->nullable();
            $table->text('customer_notes')->nullable();

            $table->enum('status', [
                'sent',
                'accepted',
                'rejected',
            ])->default('sent');

            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            $table->unique('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_quotes');
    }
};
