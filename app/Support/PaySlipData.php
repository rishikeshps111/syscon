<?php

namespace App\Support;

use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use Carbon\Carbon;

class PaySlipData
{
    public static function build(SalaryProcessing $processing, SalaryProcessingItem $item): array
    {
        $user = $item->user;
        $role = $user?->roles?->filter(fn ($role) => in_array($role->name, ['Driver', 'Housekeeping', 'Controller', 'Supervisor', 'Staff'], true))->sortBy(fn ($role) => array_search($role->name, ['Driver', 'Housekeeping', 'Controller', 'Supervisor', 'Staff'], true))->first()?->name;
        $profile = $role ? $user?->{lcfirst($role).'Profile'} : null;
        $split = SalaryCalculation::payslipSplit($processing, $item);
        $normalize = fn ($name) => strtolower(preg_replace('/\s+/', ' ', trim($name)));
        $earnings = array_fill_keys(['Basic Salary', 'VDA', 'HRA', 'Duty Incentive', 'Special Allowance', 'Bonus'], 0.0);
        $canonical = ['basic' => 'Basic Salary', 'basic salary' => 'Basic Salary', 'vda' => 'VDA', 'hra' => 'HRA',
            'duty incentive' => 'Duty Incentive', 'special allowance' => 'Special Allowance', 'bonus' => 'Bonus'];
        $deductions = ['PF' => (float) ($item->pf ?? 0), 'Professional Tax' => (float) ($item->professional_tax ?? 0),
            'ESI' => (float) ($item->esi ?? 0)] + SalaryCalculation::additionalDeductionAmounts($item, $split);
        $fixedGross = 0.0;
        foreach ($item->salary_split ?: [] as $component) {
            if (($component['selected'] ?? true) && ($component['type'] ?? '') === 'earning' && $normalize($component['name'] ?? '') !== 'duty incentive') {
                $fixedGross += (float) ($component['amount'] ?? 0);
            }
        }
        foreach ($split as $component) {
            $name = trim($component['name'] ?? 'Component');
            $normalized = $normalize($name);
            $amount = (float) ($component['amount'] ?? 0);
            if (($component['type'] ?? '') === 'earning') {
                $label = $canonical[$normalized] ?? $name;
                $earnings[$label] = ($earnings[$label] ?? 0) + $amount;
            } elseif (($component['type'] ?? '') === 'deduction' && ! in_array($normalized, ['tds', 'salary advance', 'lwf', 'penalty'], true)) {
                $label = in_array($normalized, ['pf', 'professional tax', 'esi', 'esic'], true) ? $name.' (Template)' : $name;
                $deductions[$label] = ($deductions[$label] ?? 0) + $amount;
            }
        }
        if ((float) ($item->lop ?? 0) !== 0.0) {
            $deductions['LOP'] = (float) $item->lop;
        }
        $gross = (float) ($item->total_earned ?? (($item->earned_salary ?? $item->basic_salary ?? 0) + ($item->extra_duty_incentive ?? $item->incentive ?? 0)));
        $totalDeduction = (float) ($item->total_deduction ?? $item->deduction ?? 0);
        $net = (float) ($item->net_total ?? $item->net_salary ?? 0);
        if ($split === []) {
            $earnings['Basic Salary'] = $gross;
        }
        foreach (['earnings' => $gross, 'deductions' => $totalDeduction] as $kind => $total) {
            $amounts = $kind === 'earnings' ? $earnings : $deductions;
            $remaining = round($total - array_sum($amounts), 2);
            if (round($remaining) !== 0.0) {
                if ($kind === 'earnings') $earnings['Other Earnings'] = $remaining;
                else $deductions['Other Deductions'] = $remaining;
            }
        }
        $employerPf = (float) ($item->pf ?? 0);
        $employerEsi = (float) ($item->employer_esi ?? ($fixedGross > 0 && $fixedGross <= 21000 ? round($fixedGross * .0325, 2) : 0.0));
        $logoPath = public_path('assets/img/sys-logo.png');
        $combined = trim((string) ($profile?->esic_wc ?? ''));
        $policy = trim((string) ($profile?->wc_policy ?? ''));

        return [
            'logo' => is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null,
            'period' => Carbon::create((int) $processing->year, (int) $processing->month, 1)->format('M-y'),
            'employee_code' => $user?->ref_code ?: ($user?->code ?: '-'),
            'employee_name' => $user?->name ?: '-',
            'designation' => $profile?->designation?->name ?: ($role ?: '-'),
            'department' => 'Operations',
            'uan' => $profile?->uan ?: '-',
            'present_shifts' => (float) ($item->present_days ?? $item->total_attendance_days ?? $item->total_working_days ?? 0),
            'esic' => $profile?->esic_number ?: ($combined !== '' ? $combined : ($policy !== '' ? "Workmen's Compensation" : '-')),
            'account_number' => $profile?->bank_account_number ?: ($profile?->account_number ?: '-'),
            'location' => $profile?->location?->name ?: ($processing->depot?->name ?: '-'),
            'ifsc' => strtoupper(trim((string) ($profile?->ifsc_code ?? ''))) ?: '-',
            'earnings' => $earnings, 'deductions' => $deductions,
            'gross' => $gross, 'total_deduction' => $totalDeduction, 'net' => $net,
            'employer_pf' => $employerPf, 'employer_esi' => $employerEsi,
            'employer_total' => round($employerPf + $employerEsi, 2),
            'policy' => $policy !== '' ? $policy : null,
        ];
    }
}
