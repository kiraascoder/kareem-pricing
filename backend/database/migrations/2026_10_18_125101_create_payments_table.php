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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('price_quote_id')
                ->constrained('price_quotes')
                ->restrictOnDelete();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30)
                ->default('bank_transfer');

            $table->string('sender_name', 150);
            $table->string('bank_name', 100);
            $table->timestamp('transferred_at');

            $table->string('proof_path', 255);

            $table->string('status', 30)
                ->default('pending_verification');

            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
