<?php

namespace App\Support;

use App\Models\HrLetterTemplate;
use App\Models\User;

class HrLetterRenderer
{
    public const PLACEHOLDERS = [
        '{{ employee.name }}', '{{ employee.code }}', '{{ employee.email }}', '{{ employee.phone }}',
        '{{ employee.joining_date }}', '{{ designation.name }}', '{{ employee.role }}',
        '{{ salary.components_table }}', '{{ salary.gross_salary }}', '{{ current_date }}',
        '{{ warning.reason }}', '{{ warning.incident_date }}', '{{ warning.response_due_date }}',
    ];

    public function render(HrLetterTemplate $template, User $user, array $additional = []): array
    {
        $user->loadMissing(['roles', 'staffProfile.designation', 'driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile', 'salaryComponentValues.salaryComponent']);
        $profile = $user->staffProfile ?: $user->driverProfile ?: $user->housekeepingProfile ?: $user->controllerProfile ?: $user->supervisorProfile;
        $joiningDate = $profile?->date_of_joining ?? $profile?->joining_date;
        $values = $user->salaryComponentValues;
        $gross = $values->where(fn ($value) => $value->salaryComponent?->type === 'earning')->sum('amount')
            - $values->where(fn ($value) => $value->salaryComponent?->type === 'deduction')->sum('amount');

        $plainReplacements = [
            '{{ employee.name }}' => $user->name,
            '{{ employee.code }}' => $user->code ?: '-',
            '{{ employee.email }}' => $user->email ?: '-',
            '{{ employee.phone }}' => $user->full_phone ?: '-',
            '{{ employee.joining_date }}' => $joiningDate ? $joiningDate->format('d-m-Y') : '-',
            '{{ designation.name }}' => $user->staffProfile?->designation?->name ?: '-',
            '{{ employee.role }}' => $user->roles->pluck('name')->implode(', '),
            '{{ salary.components_table }}' => '[Salary structure]',
            '{{ salary.gross_salary }}' => number_format((float) $gross, 2),
            '{{ current_date }}' => now()->format('d-m-Y'),
            '{{ warning.reason }}' => $additional['warning_reason'] ?? '-',
            '{{ warning.incident_date }}' => $additional['incident_date'] ?? '-',
            '{{ warning.response_due_date }}' => $additional['response_due_date'] ?? '-',
        ];

        $htmlReplacements = collect($plainReplacements)->map(fn ($value) => nl2br(e($value)))->all();
        $htmlReplacements['{{ salary.components_table }}'] = $this->salaryTable($values);

        return [
            'subject' => strtr($template->subject, $plainReplacements),
            'content' => strtr($template->content, $htmlReplacements),
            'header_address' => strtr($template->header_address ?? '', $plainReplacements),
            'footer_content' => strtr($template->footer_content ?? '', $plainReplacements),
        ];
    }

    private function salaryTable($values): string
    {
        if ($values->isEmpty()) {
            return '<p>No salary structure available.</p>';
        }

        $rows = $values->map(fn ($value) => '<tr><td>' . e($value->salaryComponent?->component_name ?: 'Component') . '</td><td>'
            . e(ucfirst($value->salaryComponent?->type ?: 'earning')) . '</td><td style="text-align:right">'
            . number_format((float) $value->amount, 2) . '</td></tr>')->implode('');

        return '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><thead><tr><th>Component</th><th>Type</th><th>Amount</th></tr></thead><tbody>' . $rows . '</tbody></table>';
    }
}
