<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use App\Models\EventPackage;
use Illuminate\Database\Seeder;
use RuntimeException;

class KareemMlPackageSeeder extends Seeder
{
    public function run(): void
    {
        // Harga contoh untuk pengujian aplikasi,
        // bukan harga resmi atau hasil prediksi AI.
        $packages = [
            ['workshop', 'Paket Workshop', 7500000],
            ['seminar', 'Paket Seminar', 9000000],
            ['family-gathering', 'Paket Family Gathering', 11000000],
            ['employee-gathering', 'Paket Employee Gathering', 11500000],
            ['event-bisnis', 'Paket Event Bisnis', 13000000],
        ];

        foreach ($packages as [$slug, $name, $price]) {
            $category = EventCategory::where('slug', $slug)->first();

            if (!$category) {
                throw new RuntimeException(
                    "Kategori {$slug} belum tersedia."
                );
            }

            EventPackage::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'name' => $name,
                ],
                [
                    'description' =>
                    'Paket pengujian integrasi Random Forest dan Fuzzy Sugeno.',
                    'base_price' => $price,
                    'min_participants' => 1,
                    'max_participants' => 1000,
                    'is_active' => true,
                ]
            );
        }
    }
}
