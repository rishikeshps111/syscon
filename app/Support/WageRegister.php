<?php

namespace App\Support;

use App\Models\SalaryProcessingItem;
use Carbon\Carbon;

/** One A:BK schema shared by the screen, workbook and PDF. Null means unavailable. */
class WageRegister
{
    public const GROUPS = [
        [0, 20, 'EMPLOYEE DATA', '92D050'], [20, 1, '', 'FFFFFF'],
        [21, 10, 'FIXED SALARY', 'BDD7EE'], [31, 1, '', 'FFFFFF'],
        [32, 8, 'ATTENDANCE', 'D9EAD3'], [40, 9, 'EARNINGS', 'BDD7EE'],
        [49, 1, '', 'FFFFFF'], [50, 8, 'DEDUCTIONS', 'FFE699'],
        [58, 1, 'Net Salary', '92D050'], [59, 1, '', 'FFFFFF'],
        [60, 2, 'EMPLOYER CONTRIBUTION', 'BDD7EE'], [62, 1, 'Remarks', '92D050'],
    ];

    public static function headings(): array
    {
        return ['Sl. No', 'Employee Code', 'Name of Employee', "Father's Name", 'Mobile Number', 'DOB', 'DOJ', 'Location', 'UAN number', 'ESIC NO', 'WC', 'Pan Number', 'Aadhar Number', 'Gender', 'Department', 'Bank Name', 'Account Number', 'IFSC Code', 'Designation', 'Category', '',
            'Days in a month', 'Basic Salary', 'VDA', 'Basic + VDA', 'HRA', 'Bonus', 'Special Allowance', 'Managerial Travel Allowance', 'Duty Incentive', 'Gross Salary', '',
            'MGBS incentive  (Hours)', 'Unauthorized Absence', 'Worked Shifts', 'Week Off', 'MGBS incentive (in shift  basis)', 'Total Worked shifts', 'Worked Shifts (capped for PF and ESIC)', 'Duty incentive (shifts)',
            'Basic Salary', 'VDA', 'Basic + VDA', 'HRA', 'Bonus', 'Special Allowance', 'Managerial Travel Allowance', 'Duty Incentive', 'Gross Salary', '',
            'PF 12%', 'PT', 'ESIC .75%', 'TDS', 'Salary Advance', 'LWF', 'Penalty', 'Total Deductions', '', '', '12% Employer PF', 'ESIC 3.25%', ''];
    }

    public static function title(array $report): string
    {
        return trim(($report['depot']?->name ?? '').' '.$report['monthName'].' '.$report['year'].' WAGE REGISTER');
    }

    public static function role(SalaryProcessingItem $item): ?string
    {
        $user = $item->user;
        foreach (['Driver', 'Housekeeping', 'Controller', 'Supervisor', 'Staff'] as $role) {
            if ($user?->roles?->contains('name', $role) && self::profileFor($item, $role)?->depot_id == $item->salaryProcessing?->depot_id) {
                return $role;
            }
        }

        return $user?->roles?->first(fn ($role) => in_array($role->name, ['Driver', 'Housekeeping', 'Controller', 'Supervisor', 'Staff']))?->name;
    }

    private static function profileFor(SalaryProcessingItem $item, string $role): mixed
    {
        return $item->user?->{lcfirst($role).'Profile'};
    }

    public static function row(SalaryProcessingItem $item, int $index): array
    {
        $processing = $item->salaryProcessing;
        $days = Carbon::create($processing->year, $processing->month, 1)->daysInMonth;
        $profile = ($role = self::role($item)) ? self::profileFor($item, $role) : null;
        $attr = fn ($model, $name) => $model?->getAttribute($name);
        $number = fn ($name) => $item->getAttribute($name) === null ? null : (float) $item->getAttribute($name);
        $worked = $number('total_attendance_days') ?? $number('total_working_days');
        $ratio = $worked === null ? null : min(max($worked, 0), $days) / $days;
        $split = collect($item->salary_split ?: [])->filter(fn ($c) => $c['selected'] ?? true);
        $normalize = fn ($name) => strtolower(preg_replace('/\s+/', ' ', trim($name)));
        $fixed = [];
        $earned = [];
        $deductions = [];
        foreach ($split as $component) {
            $name = $normalize($component['name'] ?? '');
            if ($name === 'basic salary') {
                $name = 'basic';
            }
            $amount = isset($component['amount']) ? (float) $component['amount'] : null;
            $calculated = isset($component['calculated_amount']) ? (float) $component['calculated_amount']
                : ($name === 'duty incentive' ? ($number('extra_duty_incentive') ?? $number('incentive')) : ($ratio !== null && $amount !== null ? $amount * $ratio : null));
            if (($component['type'] ?? '') === 'deduction') {
                $deductions[$name] = $calculated;
            } elseif (($component['type'] ?? '') === 'earning') {
                if ($name !== 'duty incentive') {
                    $fixed[$name] = $amount;
                }
                $earned[$name] = $calculated;
            }
        }
        $sumPair = fn ($values) => isset($values['basic'], $values['vda']) ? $values['basic'] + $values['vda'] : null;
        $fixedGross = $fixed !== [] && !in_array(null, $fixed, true) ? array_sum($fixed) : null;
        // The statutory migration initialized legacy totals to zero. Do not present those as paid totals.
        $legacy = ($number('total_earned') ?? 0) == 0 && ($number('net_total') ?? 0) == 0 && ($number('net_salary') ?? 0) != 0;
        $gross = $legacy ? null : ($number('total_earned') ?? $number('gross_salary'));
        $net = $legacy ? $number('net_salary') : ($number('net_total') ?? $number('net_salary'));
        $totalDeduction = $legacy ? null : $number('total_deduction');
        $basicVda = $sumPair($earned);
        $date = fn ($value) => $value ? Carbon::parse($value)->format('d-m-Y') : null;
        $componentColumns = fn ($values) => [$values['basic'] ?? null, $values['vda'] ?? null, $sumPair($values), $values['hra'] ?? null, $values['bonus'] ?? null, $values['special allowance'] ?? null, $values['managerial travel allowance'] ?? null];
        $esic = $attr($profile, 'esic_number');
        $combined = $attr($profile, 'esic_wc');
        if ($esic === null && is_string($combined) && preg_match('/^\d+$/', trim($combined))) {
            $esic = trim($combined);
        }
        $ifsc = strtoupper(trim((string) $attr($profile, 'ifsc_code')));
        $depotShortName = trim((string) $processing->depot?->short_name);
        $category = $depotShortName !== '' && $role !== null
            ? $depotShortName.($role === 'Driver' ? 'Driver' : 'Admin') : null;

        return array_merge([
            $index + 1, $item->user?->ref_code, $item->user?->name, $attr($profile, 'father_name'), $item->user?->phone,
            $date($attr($profile, 'date_of_birth')), $date($attr($profile, 'date_of_joining') ?? $attr($profile, 'joining_date')),
            $profile?->location?->name, $attr($profile, 'uan'), $esic, $attr($profile, 'wc_policy'),
            $attr($profile, 'pan_number'), $item->aadhaar_no ?: $attr($profile, 'aadhaar_number'), $attr($profile, 'gender') ?? $attr($item->user, 'gender'),
            'Operations', IfscBankName::lookup($ifsc), $attr($profile, 'bank_account_number') ?? $attr($profile, 'account_number'),
            $ifsc !== '' ? $ifsc : null, $profile?->designation?->name, $category, null,
            $days,
        ], $componentColumns($fixed), [null, $fixedGross, null,
            null, $number('unauthorized_leaves'), $number('present_days'), $number('week_off_days'), null, $worked,
            $worked === null ? null : min(max($worked, 0), $days), $worked === null ? null : max($worked - $days, 0),
        ], $componentColumns($earned), [
            $earned['duty incentive'] ?? $number('extra_duty_incentive'), $gross, null,
            $legacy ? null : $number('pf'), $legacy ? null : $number('professional_tax'), $legacy ? null : $number('esi'),
            $deductions['tds'] ?? null, $deductions['salary advance'] ?? null, $deductions['lwf'] ?? null, $deductions['penalty'] ?? null,
            $totalDeduction, $net, null,
            $basicVda === null ? null : round($basicVda * .12, 2),
            $fixedGross === null ? null : ($fixedGross <= 21000 ? round($fixedGross * .0325, 2) : 0.0),
            null,
        ]);
    }

    public static function warnings(array $rows): array
    {
        $warnings = [];
        foreach ($rows as $row) {
            $earnings = array_slice($row, 40, 8);
            $known = array_filter($earnings, fn ($v) => $v !== null);
            // Basic + VDA is a subtotal, not another earning component.
            $sum = array_sum($known) - ($row[42] ?? 0);
            if ($row[48] === null || ($known !== [] && abs($sum - $row[48]) > .02)) {
                $warnings[] = 'Employee '.($row[1] ?? '#'.$row[0]).': earning components do not reconcile with the approved gross, or saved totals are unavailable.';
            }
            if ($row[48] !== null && $row[57] !== null && $row[58] !== null && abs($row[48] - $row[57] - $row[58]) > .02) {
                $warnings[] = 'Employee '.($row[1] ?? '#'.$row[0]).': approved gross, deductions and net do not reconcile.';
            }
            if ($row[57] !== null && abs(array_sum(array_filter(array_slice($row, 50, 7), fn ($v) => $v !== null)) - $row[57]) > .02) {
                $warnings[] = 'Employee '.($row[1] ?? '#'.$row[0]).': total approved deductions include amounts not itemized in the register columns.';
            }
        }

        return $warnings;
    }
}
