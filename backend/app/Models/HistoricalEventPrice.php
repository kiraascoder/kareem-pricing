<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricalEventPrice extends Model
{
    protected $fillable = [
        'id_pemesanan',
        'jenis_acara',
        'tanggal_pelaksanaan',
        'tahun',
        'bulan',
        'hari_dalam_minggu',
        'kategori_hari',
        'harga_estimasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pelaksanaan' => 'date',
            'tahun' => 'integer',
            'bulan' => 'integer',
            'harga_estimasi' => 'decimal:2',
        ];
    }
}
