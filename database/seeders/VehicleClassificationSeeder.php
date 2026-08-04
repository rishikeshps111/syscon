<?php

namespace Database\Seeders;

use App\Models\VehicleClassification;
use Illuminate\Database\Seeder;

class VehicleClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            'Express',
            'Deluxe',
            'Super Deluxe',
            'Luxury',
            'Super Luxury',
            '9M AC',
            '9M Non AC',
            '12M AC',
            '12M Non AC',
        ];

        foreach ($titles as $title) {
            VehicleClassification::updateOrCreate(['title' => $title], ['is_active' => true]);
        }
    }
}
