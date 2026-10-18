<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('booking_code', 30)->unique();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('event_package_id')
                ->constrained('event_packages')
                ->restrictOnDelete();

            $table->date('event_date');
            $table->string('event_location', 255);
            $table->unsignedInteger('participants');

            $table->text('customer_notes')->nullable();

            $table->decimal('base_price_snapshot', 15, 2);

            $table->string('status', 30)->default('pending');

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['event_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
