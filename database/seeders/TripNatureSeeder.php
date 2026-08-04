<?php

namespace Database\Seeders;

use App\Models\TripNature;
use Illuminate\Database\Seeder;

class TripNatureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['S/OFF', 'ADD ON', 'Special Service', 'Holiday Service', 'Emergency Service', 'Extra Duty'] as $title) {
            TripNature::updateOrCreate(['title' => $title], ['is_active' => true]);
        }
    }
}
