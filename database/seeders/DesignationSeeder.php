<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Level;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            [
                'name' => 'HR Executive',
                'department' => 'Human Resources',
                'level' => 'Junior',
                'reporting_to' => 'Supervisor',
                'description' => 'Handles HR operations and employee coordination',
                'is_active' => true,
            ],
            [
                'name' => 'Operations Supervisor',
                'department' => 'Operations',
                'level' => 'Senior',
                'reporting_to' => 'Controller',
                'description' => 'Supervises daily transport operations',
                'is_active' => true,
            ],
            [
                'name' => 'Finance Officer',
                'department' => 'Finance',
                'level' => 'Mid Level',
                'reporting_to' => 'Supervisor',
                'description' => 'Maintains financial records and approvals',
                'is_active' => true,
            ],
            [
                'name' => 'Admin Coordinator',
                'department' => 'Administration',
                'level' => 'Mid Level',
                'reporting_to' => 'Supervisor',
                'description' => 'Coordinates administrative activities',
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance Lead',
                'department' => 'Maintenance',
                'level' => 'Lead',
                'reporting_to' => 'Controller',
                'description' => 'Leads fleet maintenance follow-ups',
                'is_active' => true,
            ],
            [
                'name' => 'Support Associate',
                'department' => 'Customer Support',
                'level' => 'Entry Level',
                'reporting_to' => 'Supervisor',
                'description' => 'Supports customer query handling',
                'is_active' => false,
            ],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $department = Department::where('name', $record['department'])->first();
                $level = Level::where('name', $record['level'])->first();
                $role = Role::where('name', $record['reporting_to'])
                    ->where('name', '!=', 'Super Admin')
                    ->first();

                if (! $department || ! $level || ! $role) {
                    continue;
                }

                $designation = Designation::firstOrCreate(
                    ['name' => $record['name']],
                    [
                        'department_id' => $department->id,
                        'level_id' => $level->id,
                        'reporting_to' => $role->id,
                        'description' => $record['description'],
                        'is_active' => $record['is_active'],
                    ]
                );

                if (! $designation->code) {
                    $designation->code = generate_code('Designation Module', $designation->id, 3, 'DSG');
                    $designation->save();
                }
            }
        });
    }
}
