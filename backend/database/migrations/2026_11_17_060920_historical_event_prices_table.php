<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_event_prices', function (Blueprint $table) {
            $table->id();

            $table->string('id_pemesanan', 50)->unique();

            $table->string('jenis_acara', 100);

            $table->date('tanggal_pelaksanaan');

            $table->unsignedSmallInteger('tahun');

            $table->unsignedTinyInteger('bulan');

            $table->string('hari_dalam_minggu', 30);

            $table->string('kategori_hari', 30);

            $table->decimal('harga_estimasi', 15, 2);

            $table->timestamps();

            $table->index([
                'jenis_acara',
                'tanggal_pelaksanaan',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_event_prices');
    }
};
