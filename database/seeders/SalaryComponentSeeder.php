<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\SalaryComponent;
use App\Models\SalaryComponentAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor', 'Housekeeping'])
            ->where('guard_name', 'web')
            ->get()
            ->keyBy('name');

        $staffTemplates = $this->staffTemplates();
        $zeroValues = array_fill_keys(array_keys($this->components()), 0);
        $targets = collect();

        if ($roles->has('Staff')) {
            $targets = $targets->concat(
                Designation::orderBy('name')->get(['id', 'name'])->map(function (Designation $designation) use ($roles, $staffTemplates, $zeroValues) {
                    return [
                        'role_id' => $roles['Staff']->id,
                        'designation_id' => $designation->id,
                        'values' => $staffTemplates[Str::lower(trim($designation->name))] ?? $zeroValues,
                    ];
                })
            );
        }

        foreach (['Driver', 'Controller', 'Supervisor', 'Housekeeping'] as $roleName) {
            if ($roles->has($roleName)) {
                $targets->push([
                    'role_id' => $roles[$roleName]->id,
                    'designation_id' => null,
                    'values' => $zeroValues,
                ]);
            }
        }

        foreach ($targets as $target) {
            foreach ($this->components() as $componentName => $componentData) {
                $component = SalaryComponent::query()
                    ->where('component_name', $componentName)
                    ->whereHas('assignments', fn ($query) => $query
                        ->where('role_id', $target['role_id'])
                        ->where('designation_id', $target['designation_id']))
                    ->first();

                $attributes = $componentData + [
                    'component_name' => $componentName,
                    'default_value' => $target['values'][$componentName],
                    'is_applicable' => true,
                    'is_editable_in_payroll' => true,
                ];

                if ($component) {
                    $component->update($attributes);
                } else {
                    $component = SalaryComponent::create($attributes + ['code' => null]);
                }

                if (! $component->code) {
                    $component->update([
                        'code' => generate_code(SalaryComponent::PREFIX_MODULE, $component->id, 3, 'SC'),
                    ]);
                }

                $component->assignments()->delete();
                SalaryComponentAssignment::create([
                    'salary_component_id' => $component->id,
                    'role_id' => $target['role_id'],
                    'designation_id' => $target['designation_id'],
                ]);
            }
        }
    }

    /**
     * Basic + VDA and Gross Salary are intentionally excluded because the
     * application calculates both totals from their individual components.
     */
    private function components(): array
    {
        return [
            'Basic' => [
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_mandatory' => true,
            ],
            'VDA' => [
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_mandatory' => true,
            ],
            'HRA' => [
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_mandatory' => false,
            ],
            'Bonus' => [
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_mandatory' => false,
            ],
            'Special Allowance' => [
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_mandatory' => false,
            ],
            'Managerial Travel Allowance' => [
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_mandatory' => false,
            ],
            'Duty Incentive' => [
                'type' => 'earning',
                'calculation_type' => 'fixed',
                'is_mandatory' => false,
            ],
        ];
    }

    /**
     * Values transcribed from the "Salary templates" worksheet.
     *
     * @return array<string, array<string, int>>
     */
    private function staffTemplates(): array
    {
        $rows = [
            ['Director-Finance', 73300, 0, 29320, 0, 37380, 0, 0],
            ['FMC', 3168, 7838, 1267, 1000, 4694, 0, 0],
            ['NLG Cleaner', 2645, 7838, 784, 1000, 0, 0, 0],
            ['NLG Controller', 3658, 7838, 1815, 1000, 0, 0, 0],
            ['NLG Driver', 4319, 7838, 1728, 1000, 6794, 0, 0],
            ['NLG MIS', 3168, 7838, 1299, 1000, 10195, 0, 0],
            ['NLG Supervisor 1', 5375, 7838, 2245, 1101, 8280, 0, 0],
            ['NLG Supervisor 2', 4160, 7838, 1664, 1000, 6996, 0, 0],
            ['NLG Supervisor 3', 4160, 7838, 1664, 1000, 4998, 0, 0],
            ['NLG Supervisor 4', 4160, 7838, 1664, 1000, 2998, 0, 0],
            ['NLG Supervisor 5', 4160, 7838, 1664, 1000, 1999, 0, 0],
            ['Operations Manager', 100000, 0, 40000, 10000, 20000, 30000, 0],
            ['WGL Depot Manager', 8365, 7838, 3441, 3000, 7818, 0, 0],
            ['WGL HR Associate', 5556, 7838, 2318, 1116, 8352, 0, 0],
            ['VP Operations', 50700, 0, 20280, 0, 25020, 0, 0],
            ['VP- Human Resources', 72600, 0, 29040, 0, 36360, 0, 0],
            ['WGL Cleaner 1', 2645, 7930, 1190, 1000, 6534, 0, 0],
            ['WGL Cleaner 4', 2645, 7838, 1058, 1000, 725, 0, 0],
            ['WGL Cleaner 2', 2645, 7838, 1058, 1000, 3725, 0, 0],
            ['WGL Cleaner 3', 2645, 7838, 1058, 1000, 1725, 0, 0],
            ['WGL Controller 2', 3658, 7838, 1724, 1000, 0, 0, 0],
            ['WGL Controller 1', 3658, 7838, 1463, 1000, 1471, 0, 0],
            ['WGL MIS', 3532, 7838, 1508, 1000, 7498, 0, 0],
            ['WGL Driver', 5375, 7838, 2245, 1101, 8280, 0, 0],
            ['WGL Supervisor', 5365, 7838, 2241, 1100, 8278, 0, 0],
            ['NLG Depot Manager', 4160, 7838, 1664, 1000, 10338, 0, 0],
        ];

        return collect($rows)->mapWithKeys(function (array $row) {
            [$designation, $basic, $vda, $hra, $bonus, $specialAllowance, $managerialTravelAllowance, $dutyIncentive] = $row;

            return [
                Str::lower($designation) => [
                    'Basic' => $basic,
                    'VDA' => $vda,
                    'HRA' => $hra,
                    'Bonus' => $bonus,
                    'Special Allowance' => $specialAllowance,
                    'Managerial Travel Allowance' => $managerialTravelAllowance,
                    'Duty Incentive' => $dutyIncentive,
                ],
            ];
        })->all();
    }
}
