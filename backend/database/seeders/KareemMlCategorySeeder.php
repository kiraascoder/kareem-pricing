<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class KareemMlCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Workshop',
                'slug' => 'workshop',
            ],
            [
                'name' => 'Seminar',
                'slug' => 'seminar',
            ],
            [
                'name' => 'Family Gathering',
                'slug' => 'family-gathering',
            ],
            [
                'name' => 'Employee Gathering',
                'slug' => 'employee-gathering',
            ],
            [
                'name' => 'Event Bisnis',
                'slug' => 'event-bisnis',
            ],
        ];

        foreach ($categories as $category) {
            EventCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' =>
                    'Kategori event yang didukung model AI Kareem Pricing.',
                    'is_active' => true,
                ]
            );
        }
    }
}
