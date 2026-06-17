<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\SalaryComponent;
use App\Models\SalaryComponentAssignment;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['component_name' => 'Basic', 'type' => 'earning', 'calculation_type' => 'fixed', 'default_value' => 0, 'is_mandatory' => true],
            ['component_name' => 'VDA', 'type' => 'earning', 'calculation_type' => 'fixed', 'default_value' => 0, 'is_mandatory' => true],
            ['component_name' => 'HRA', 'type' => 'earning', 'calculation_type' => 'fixed', 'default_value' => 0, 'is_mandatory' => false],
            ['component_name' => 'Special Allowance', 'type' => 'earning', 'calculation_type' => 'fixed', 'default_value' => 0, 'is_mandatory' => false],
            ['component_name' => 'Conveyance Allowance', 'type' => 'earning', 'calculation_type' => 'fixed', 'default_value' => 0, 'is_mandatory' => false],
            ['component_name' => 'Bonus', 'type' => 'earning', 'calculation_type' => 'fixed', 'default_value' => 0, 'is_mandatory' => false],
            ['component_name' => 'PF', 'type' => 'deduction', 'calculation_type' => 'fixed', 'default_value' => 0, 'is_mandatory' => false],
            ['component_name' => 'ESI', 'type' => 'deduction', 'calculation_type' => 'fixed', 'default_value' => 0, 'is_mandatory' => false],
        ];

        $roles = Role::whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor'])
            ->get()
            ->keyBy('name');
        $staffDesignations = Designation::orderBy('name')->get(['id']);

        foreach ($components as $index => $componentData) {
            $component = SalaryComponent::firstOrCreate(
                ['component_name' => $componentData['component_name']],
                $componentData + [
                    'code' => null,
                    'is_applicable' => true,
                    'is_editable_in_payroll' => true,
                ]
            );

            if (! $component->code) {
                $component->code = generate_code(SalaryComponent::PREFIX_MODULE, $component->id, 3, 'SC');
                $component->save();
            }

            foreach (['Driver', 'Controller', 'Supervisor'] as $roleName) {
                if (! $roles->has($roleName)) {
                    continue;
                }

                SalaryComponentAssignment::firstOrCreate([
                    'salary_component_id' => $component->id,
                    'role_id' => $roles[$roleName]->id,
                    'designation_id' => null,
                ]);
            }

            if ($roles->has('Staff')) {
                foreach ($staffDesignations as $designation) {
                    SalaryComponentAssignment::firstOrCreate([
                        'salary_component_id' => $component->id,
                        'role_id' => $roles['Staff']->id,
                        'designation_id' => $designation->id,
                    ]);
                }
            }
        }
    }
}
