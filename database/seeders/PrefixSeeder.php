<?php

namespace Database\Seeders;

use App\Models\Prefix;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrefixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            ['prefix' => 'ST', 'module' => 'State Module', 'is_active' => true],
            ['prefix' => 'DS', 'module' => 'District Module', 'is_active' => true],
            ['prefix' => 'LOC', 'module' => 'Location Module', 'is_active' => true],
            ['prefix' => 'SRT', 'module' => 'Service Type Module', 'is_active' => true],
            ['prefix' => 'RT', 'module' => 'Route Module', 'is_active' => true],
            ['prefix' => 'TSU', 'module' => 'Trip Setup Module', 'is_active' => true],
            ['prefix' => 'VC', 'module' => 'Vehicle Classification Module', 'is_active' => true],
            ['prefix' => 'DOCT', 'module' => 'Document Type Module', 'is_active' => true],
            ['prefix' => 'DPM', 'module' => 'Depot Module', 'is_active' => true],
        ];

        foreach ($records as $record) {
            Prefix::firstOrCreate(
                ['module' => $record['module']],
                $record
            );
        }
    }
}
