<?php

namespace Database\Seeders;

use App\Models\HistoricalEventPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HistoricalEventPriceSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path(
            'seeders/data/dataset_dummy_kareem_event_jenis_tanggal.csv'
        );

        if (!is_file($path)) {
            throw new RuntimeException(
                "File dataset tidak ditemukan: {$path}"
            );
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException(
                'Gagal membuka file dataset.'
            );
        }

        try {
            $headers = fgetcsv($handle);

            if (!$headers) {
                throw new RuntimeException(
                    'Header CSV tidak ditemukan.'
                );
            }

            $headers[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $headers[0]
            );

            $required = [
                'id_pemesanan',
                'jenis_acara',
                'tanggal_pelaksanaan',
                'tahun',
                'bulan',
                'hari_dalam_minggu',
                'kategori_hari',
                'harga_estimasi',
            ];

            $missing = array_diff($required, $headers);

            if (!empty($missing)) {
                throw new RuntimeException(
                    'Kolom CSV tidak ditemukan: ' .
                        implode(', ', $missing)
                );
            }

            $rows = [];
            $line = 1;
            $now = now();

            while (($values = fgetcsv($handle)) !== false) {
                $line++;

                if ($values === [null]) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    throw new RuntimeException(
                        "Jumlah kolom tidak sesuai pada baris {$line}."
                    );
                }

                $data = array_combine($headers, $values);

                $rows[] = [
                    'id_pemesanan' => trim($data['id_pemesanan']),
                    'jenis_acara' => trim($data['jenis_acara']),
                    'tanggal_pelaksanaan' =>
                    trim($data['tanggal_pelaksanaan']),
                    'tahun' => (int) $data['tahun'],
                    'bulan' => (int) $data['bulan'],
                    'hari_dalam_minggu' =>
                    trim($data['hari_dalam_minggu']),
                    'kategori_hari' =>
                    trim($data['kategori_hari']),
                    'harga_estimasi' =>
                    (float) $data['harga_estimasi'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::transaction(function () use ($rows) {
                foreach (array_chunk($rows, 100) as $chunk) {
                    HistoricalEventPrice::upsert(
                        $chunk,
                        ['id_pemesanan'],
                        [
                            'jenis_acara',
                            'tanggal_pelaksanaan',
                            'tahun',
                            'bulan',
                            'hari_dalam_minggu',
                            'kategori_hari',
                            'harga_estimasi',
                            'updated_at',
                        ]
                    );
                }
            });

            $this->command?->info(
                count($rows) .
                    ' baris dataset berhasil diproses.'
            );
        } finally {
            fclose($handle);
        }
    }
}
