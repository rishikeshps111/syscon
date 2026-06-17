<?php

namespace App\Exports;

use App\Models\SalaryComponent;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalaryComponentExport implements FromCollection, WithHeadings
{
    public function __construct(private $query = null)
    {
        $this->query = $query ?: SalaryComponent::with(['assignments.role', 'assignments.designation']);
    }

    public function collection()
    {
        return $this->query->get()->map(function (SalaryComponent $component) {
            return [
                'Code' => $component->code,
                'Roles' => $this->assignmentLabel($component),
                'Component Name' => $component->component_name,
                'Type' => ucfirst($component->type),
                'Applicable' => $component->is_applicable ? 'Yes' : 'No',
                'Calculation Type' => str($component->calculation_type)->replace('_', ' ')->title(),
                'Default Value' => $component->default_value,
                'Editable in Payroll' => $component->is_editable_in_payroll ? 'Yes' : 'No',
                'Mandatory' => $component->is_mandatory ? 'Yes' : 'No',
                'Created At' => $component->created_at?->format('d M Y'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Roles',
            'Component Name',
            'Type',
            'Applicable',
            'Calculation Type',
            'Default Value',
            'Editable in Payroll',
            'Mandatory',
            'Created At',
        ];
    }

    private function assignmentLabel(SalaryComponent $component): string
    {
        return $component->assignments
            ->groupBy(fn ($assignment) => $assignment->role?->name ?? 'Role')
            ->map(function ($assignments, string $roleName) {
                $designations = $assignments
                    ->pluck('designation.name')
                    ->filter()
                    ->values();

                return $designations->isEmpty()
                    ? $roleName
                    : $roleName . ' (' . $designations->implode(', ') . ')';
            })
            ->implode(', ');
    }
}
