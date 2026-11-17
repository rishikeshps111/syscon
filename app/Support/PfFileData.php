<?php

namespace App\Support;

use App\Models\SalaryProcessingItem;
use Carbon\Carbon;

class PfFileData
{
    public const HEADINGS = [
        'UAN', 'NAME', 'GROSS SALARY', 'EPF WAGES (BASIC + VDA)',
        'PF ( EMPLOYEE CONTRIBUTION', 'WORKED SHIFTS (CAPPING AT 30/31 DEPENDING UPON THE MONTH)',
    ];

    public static function uan(SalaryProcessingItem $item): string
    {
        $role = WageRegister::role($item);

        return trim((string) ($role ? $item->user?->{lcfirst($role).'Profile'}?->uan : ''));
    }

    public static function row(SalaryProcessingItem $item): array
    {
        $processing = $item->salaryProcessing;
        $calendarDays = Carbon::create((int) $processing->year, (int) $processing->month, 1)->daysInMonth;
        $worked = $item->total_attendance_days ?? $item->total_working_days;
        $days = $worked === null ? null : min(max((float) $worked, 0), $calendarDays);
        $wages = 0.0;
        $hasWages = false;
        foreach ($item->salary_split ?: [] as $component) {
            $name = strtolower(preg_replace('/\s+/', ' ', trim((string) ($component['name'] ?? ''))));
            if (! ($component['selected'] ?? true) || ($component['type'] ?? '') !== 'earning'
                || ! in_array($name, ['basic', 'basic salary', 'vda'], true)) {
                continue;
            }
            $amount = $component['calculated_amount'] ?? null;
            if ($amount === null && isset($component['amount']) && $days !== null) {
                $amount = (float) $component['amount'] * $days / $calendarDays;
            }
            if ($amount === null) {
                return [self::uan($item), $item->user?->name, self::gross($item), null, (float) $item->pf, $days];
            }
            $wages += (float) $amount;
            $hasWages = true;
        }

        return [self::uan($item), $item->user?->name, self::gross($item),
            $hasWages ? round($wages, 2) : null, (float) $item->pf, $days];
    }

    private static function gross(SalaryProcessingItem $item): ?float
    {
        $legacy = (float) ($item->total_earned ?? 0) === 0.0
            && (float) ($item->net_total ?? 0) === 0.0 && (float) ($item->net_salary ?? 0) !== 0.0;

        $gross = $item->total_earned ?? $item->gross_salary;

        return $legacy ? null : ($gross === null ? null : (float) $gross);
    }
}
