<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\SalaryComponents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class UserSalaryComponentValueSeeder extends Seeder
{
    private const PROFILE_RELATIONS = [
        'Staff' => 'staffProfile',
        'Controller' => 'controllerProfile',
        'Supervisor' => 'supervisorProfile',
        'Driver' => 'driverProfile',
        'Housekeeping' => 'housekeepingProfile',
    ];

    public function run(): void
    {
        foreach (self::PROFILE_RELATIONS as $roleName => $profileRelation) {
            User::role($roleName)
                ->with($profileRelation)
                ->get()
                ->each(function (User $user) use ($roleName, $profileRelation) {
                    $profile = $user->{$profileRelation};

                    if (! $profile) {
                        return;
                    }

                    $designationId = $roleName === 'Staff' ? $profile->designation_id : null;
                    $components = SalaryComponents::forRole($roleName, $designationId);

                    if ($components->isEmpty()) {
                        return;
                    }

                    $salaryValues = in_array($roleName, ['Driver', 'Housekeeping'], true)
                        ? $this->driverSalaryValues((float) $profile->salary)
                        : $this->profileSalaryValues($profile);

                    $amounts = $components->mapWithKeys(function ($component) use ($salaryValues) {
                        $key = $this->componentKey($component->component_name);

                        return [
                            $component->id => $salaryValues[$key] ?? (float) $component->default_value,
                        ];
                    })->all();

                    SalaryComponents::sync($user, $amounts);
                });
        }
    }

    private function profileSalaryValues(Model $profile): array
    {
        return [
            'basic' => (float) $profile->basic,
            'vda' => (float) $profile->vda,
            'hra' => (float) $profile->hra,
            'special_allowance' => (float) $profile->special_allowance,
            'conveyance_allowance' => (float) $profile->conveyance_allowance,
            'bonus' => (float) $profile->bonus,
        ];
    }

    private function driverSalaryValues(float $salary): array
    {
        $values = [
            'basic' => round($salary * 0.50, 2),
            'vda' => round($salary * 0.10, 2),
            'hra' => round($salary * 0.15, 2),
            'special_allowance' => round($salary * 0.10, 2),
            'conveyance_allowance' => round($salary * 0.05, 2),
        ];
        $values['bonus'] = round($salary - array_sum($values), 2);

        return $values;
    }

    private function componentKey(string $componentName): string
    {
        return str($componentName)
            ->lower()
            ->replace([' + ', ' / ', ' ', '-'], '_')
            ->toString();
    }
}
