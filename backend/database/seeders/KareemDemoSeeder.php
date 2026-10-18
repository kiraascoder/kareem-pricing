<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventCategory;
use App\Models\EventPackage;
use Illuminate\Support\Str;

class KareemDemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Wedding',
                'description' => 'Paket penyelenggaraan acara pernikahan.',
                'packages' => [
                    ['Wedding Basic', 15000000, 50, 150],
                    ['Wedding Premium', 35000000, 100, 400],
                ],
            ],
            [
                'name' => 'Corporate',
                'description' => 'Paket acara perusahaan dan bisnis.',
                'packages' => [
                    ['Corporate Meeting', 10000000, 20, 100],
                    ['Corporate Gathering', 25000000, 100, 500],
                ],
            ],
            [
                'name' => 'Concert',
                'description' => 'Paket pertunjukan musik dan hiburan.',
                'packages' => [
                    ['Mini Concert', 30000000, 100, 500],
                    ['Concert Festival', 75000000, 500, 2000],
                ],
            ],
            [
                'name' => 'Gathering',
                'description' => 'Paket kegiatan komunitas dan keluarga.',
                'packages' => [
                    ['Community Gathering', 8000000, 30, 150],
                    ['Grand Gathering', 20000000, 100, 500],
                ],
            ],
        ];

        foreach ($categories as $item) {
            $category = EventCategory::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'is_active' => true,
                ]
            );

            foreach ($item['packages'] as $package) {
                EventPackage::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $package[0],
                    ],
                    [
                        'description' => 'Paket simulasi untuk pengujian Kareem Pricing.',
                        'base_price' => $package[1],
                        'min_participants' => $package[2],
                        'max_participants' => $package[3],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
