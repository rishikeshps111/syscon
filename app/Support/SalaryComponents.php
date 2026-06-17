<?php

namespace App\Support;

use App\Models\SalaryComponent;
use App\Models\User;
use App\Models\UserSalaryComponentValue;
use Illuminate\Support\Collection;

class SalaryComponents
{
    public static function forRole(string $roleName, ?int $designationId = null): Collection
    {
        return SalaryComponent::query()
            ->with(['assignments.role', 'assignments.designation'])
            ->whereHas('assignments', function ($query) use ($roleName, $designationId) {
                $query->whereHas('role', fn ($roleQuery) => $roleQuery->where('name', $roleName));

                if ($roleName === 'Staff' && $designationId) {
                    $query->where(function ($designationQuery) use ($designationId) {
                        $designationQuery->whereNull('designation_id');
                        $designationQuery->orWhere('designation_id', $designationId);
                    });
                }
            })
            ->orderByRaw("CASE type WHEN 'earning' THEN 1 ELSE 2 END")
            ->orderBy('component_name')
            ->get();
    }

    public static function valuesFor(?User $user): Collection
    {
        if (! $user?->exists) {
            return collect();
        }

        return UserSalaryComponentValue::where('user_id', $user->id)
            ->pluck('amount', 'salary_component_id');
    }

    public static function sync(User $user, array $amounts): array
    {
        $componentIds = collect($amounts)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $components = SalaryComponent::whereIn('id', $componentIds)->get()->keyBy('id');
        $totals = ['earning' => 0.0, 'deduction' => 0.0];

        foreach ($amounts as $componentId => $amount) {
            $component = $components->get((int) $componentId);

            if (! $component) {
                continue;
            }

            $value = filled($amount) ? (float) $amount : 0.0;
            $totals[$component->type] += $value;

            UserSalaryComponentValue::updateOrCreate(
                ['user_id' => $user->id, 'salary_component_id' => $component->id],
                ['amount' => $value]
            );
        }

        UserSalaryComponentValue::where('user_id', $user->id)
            ->whereNotIn('salary_component_id', $componentIds)
            ->delete();

        return $totals;
    }

    public static function legacyProfileSalaryData(array $amounts): array
    {
        $components = SalaryComponent::whereIn('id', array_keys($amounts))->get();
        $legacy = [
            'basic' => 0.0,
            'vda' => 0.0,
            'hra' => null,
            'special_allowance' => null,
            'conveyance_allowance' => null,
            'bonus' => null,
        ];

        foreach ($components as $component) {
            $key = str($component->component_name)->lower()->replace([' + ', ' / ', ' ', '-'], '_')->toString();
            $value = filled($amounts[$component->id] ?? null) ? (float) $amounts[$component->id] : null;

            if (array_key_exists($key, $legacy)) {
                $legacy[$key] = $value;
            }
        }

        $basic = (float) ($legacy['basic'] ?? 0);
        $vda = (float) ($legacy['vda'] ?? 0);
        $earnings = $components
            ->where('type', 'earning')
            ->sum(fn ($component) => (float) ($amounts[$component->id] ?? 0));

        return $legacy + [
            'basic_vda' => $basic + $vda,
            'gross_salary' => $earnings,
            'salary' => $earnings,
        ];
    }
}
