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

        $targets = collect(['Driver', 'Controller', 'Supervisor'])
            ->map(fn ($roleName) => $roles->get($roleName))
            ->filter()
            ->map(fn ($role) => ['role_id' => $role->id, 'designation_id' => null]);

        if ($roles->has('Staff')) {
            $targets = $targets->concat($staffDesignations->map(fn ($designation) => [
                'role_id' => $roles['Staff']->id,
                'designation_id' => $designation->id,
            ]));
        }

        foreach ($targets as $target) {
            foreach ($components as $componentData) {
                $component = SalaryComponent::query()
                    ->where('component_name', $componentData['component_name'])
                    ->whereHas('assignments', fn ($query) => $query
                        ->where('role_id', $target['role_id'])
                        ->where('designation_id', $target['designation_id']))
                    ->first();

                if (! $component) {
                    $component = SalaryComponent::create($componentData + [
                        'code' => null,
                        'is_applicable' => true,
                        'is_editable_in_payroll' => true,
                    ]);
                } else {
                    $component->update($componentData + [
                        'is_applicable' => true,
                        'is_editable_in_payroll' => true,
                    ]);
                }

                if (! $component->code) {
                    $component->code = generate_code(SalaryComponent::PREFIX_MODULE, $component->id, 3, 'SC');
                    $component->save();
                }

                $component->assignments()->delete();
                SalaryComponentAssignment::create($target + [
                    'salary_component_id' => $component->id,
                ]);
            }
        }
    }
}
