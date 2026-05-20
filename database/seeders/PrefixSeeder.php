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
            ['prefix' => 'CC', 'module' => 'Complaint Category Module', 'is_active' => true],
            ['prefix' => 'DPM', 'module' => 'Depot Module', 'is_active' => true],
            ['prefix' => 'BL', 'module' => 'Branch Location Module', 'is_active' => true],
            ['prefix' => 'DPT', 'module' => 'Department Module', 'is_active' => true],
            ['prefix' => 'LVL', 'module' => 'Level Module', 'is_active' => true],
            ['prefix' => 'DSG', 'module' => 'Designation Module', 'is_active' => true],
            ['prefix' => 'HDT', 'module' => 'HRMS Document Type Module', 'is_active' => true],
            ['prefix' => 'LV', 'module' => 'Leave Type Module', 'is_active' => true],
            ['prefix' => 'SH', 'module' => 'Shift Setting Module', 'is_active' => true],
            ['prefix' => 'HOL', 'module' => 'Holiday Module', 'is_active' => true],
            ['prefix' => 'STF', 'module' => 'Staff Management Module', 'is_active' => true],
            ['prefix' => 'CTL', 'module' => 'Controller Management Module', 'is_active' => true],
            ['prefix' => 'SUP', 'module' => 'Supervisor Management Module', 'is_active' => true],
            ['prefix' => 'DRV', 'module' => 'Driver Management Module', 'is_active' => true],
        ];

        foreach ($records as $record) {
            Prefix::firstOrCreate(
                ['module' => $record['module']],
                $record
            );
        }
    }
}
