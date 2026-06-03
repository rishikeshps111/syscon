<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            ['name' => 'Human Resources', 'is_active' => true],
            ['name' => 'Operations', 'is_active' => true],
            ['name' => 'Finance', 'is_active' => true],
            ['name' => 'Administration', 'is_active' => true],
            ['name' => 'Maintenance', 'is_active' => true],
            ['name' => 'Fleet Compliance', 'is_active' => true],
            ['name' => 'Route Planning', 'is_active' => true],
            ['name' => 'Vendor Management', 'is_active' => true],
            ['name' => 'Customer Support', 'is_active' => false],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $department = Department::firstOrCreate(
                    ['name' => $record['name']],
                    ['is_active' => $record['is_active']]
                );

                if (! $department->code) {
                    $department->code = generate_code('Department Module', $department->id, 3, 'DPT');
                    $department->save();
                }
            }
        });
    }
}
