<?php

namespace App\Support;

use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use Carbon\Carbon;

class SalaryCalculation
{
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
