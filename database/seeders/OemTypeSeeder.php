<?php

namespace Database\Seeders;

use App\Models\OemType;
use Illuminate\Database\Seeder;

class OemTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['9m', '12m', 'city bus', 'long tour bus'] as $name) {
            OemType::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
