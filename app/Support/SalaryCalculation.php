<?php

namespace App\Support;

use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use Carbon\Carbon;

class SalaryCalculation
{
    public static function esicContributions(float $fixedGross, float $earnedGross): array
    {
        $eligible = $fixedGross <= 21000;

        return [
            'esi' => $eligible ? round($earnedGross * .0075) : 0.0,
            'employer_esi' => $eligible ? round($earnedGross * .0325, 2) : 0.0,
        ];
    }

    public static function split(array $components, float $workedDays, int $calendarDays, float $incentive): array
    {
        $ratio = $calendarDays > 0 ? min(max($workedDays, 0), $calendarDays) / $calendarDays : 0;

        return array_map(function (array $component) use ($ratio, $incentive) {
            $component['selected'] = $component['selected'] ?? true;
            $component['calculated_amount'] = strtolower(trim($component['name'] ?? '')) === 'duty incentive'
                ? $incentive
                : (float) ($component['amount'] ?? 0) * $ratio;

            return $component;
        }, $components);
    }

    public static function wholeRupeeDeductions(array $row): array
    {
        $difference = 0;
        if (array_key_exists('pf_applicable', $row) && ! $row['pf_applicable']) {
            $difference -= (float) ($row['pf'] ?? 0);
            $row['pf'] = 0.0;
        }
        foreach (['esi', 'tds', 'salary_advance', 'lwf', 'penalty'] as $field) {
            $original = (float) ($row[$field] ?? 0);
            $row[$field] = round($original);
            $difference += $row[$field] - $original;
        }
        $row['total_deduction'] = round((float) ($row['total_deduction'] ?? $row['deduction'] ?? 0) + $difference, 2);
        $row['net_total'] = round((float) ($row['net_total'] ?? $row['net_salary'] ?? 0) - $difference, 2);

        return $row;
    }

    public static function additionalDeductionAmounts(SalaryProcessingItem $item, array $split): array
    {
        $amounts = [];
        foreach (['tds' => 'TDS', 'salary_advance' => 'Salary Advance', 'lwf' => 'LWF', 'penalty' => 'Penalty'] as $field => $label) {
            $amounts[$label] = (float) ($item->{$field} ?? 0);
            foreach ($split as $component) {
                $name = strtolower(preg_replace('/\s+/', ' ', trim($component['name'] ?? '')));
                if (($component['type'] ?? '') === 'deduction' && $name === strtolower($label)) {
                    $amounts[$label] += (float) ($component['amount'] ?? 0);
                }
            }
            $amounts[$label] = round($amounts[$label], 2);
        }

        return $amounts;
    }

    public static function payslipSplit(SalaryProcessing $processing, SalaryProcessingItem $item): array
    {
        $days = Carbon::create((int) $processing->year, (int) $processing->month, 1)->daysInMonth;
        $split = self::split($item->salary_split ?: [], (float) ($item->total_attendance_days ?? $item->total_working_days ?? 0), $days,
            (float) ($item->extra_duty_incentive ?? $item->incentive ?? 0));

        return array_values(array_map(function (array $component) {
            $component['amount'] = $component['calculated_amount'];

            return $component;
        }, array_filter($split, fn ($component) => $component['selected'])));
    }
}
