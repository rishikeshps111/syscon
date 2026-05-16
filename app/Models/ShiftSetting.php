<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'number_of_shifts_per_day',
    'code',
    'shift_name',
    'start_time',
    'end_time',
    'break_duration_minutes',
    'total_working_hours',
    'grace_time_minutes',
    'minimum_working_hours',
    'check_in_window_start',
    'check_in_window_end',
    'check_out_flexibility',
    'enable_overtime',
    'is_active',
])]
#[Table('shift_settings')]
class ShiftSetting extends Model
{
    use HasFactory;

    public const SHIFT_NAMES = [
        'Morning Shift',
        'Evening Shift',
    ];

    protected function casts(): array
    {
        return [
            'number_of_shifts_per_day' => 'integer',
            'break_duration_minutes' => 'integer',
            'total_working_hours' => 'decimal:2',
            'grace_time_minutes' => 'integer',
            'minimum_working_hours' => 'decimal:2',
            'check_in_window_start' => 'boolean',
            'check_in_window_end' => 'boolean',
            'check_out_flexibility' => 'boolean',
            'enable_overtime' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getFormattedStartTimeAttribute(): string
    {
        return $this->formatTime($this->start_time);
    }

    public function getFormattedEndTimeAttribute(): string
    {
        return $this->formatTime($this->end_time);
    }

    private function formatTime(?string $time): string
    {
        if (! $time) {
            return '';
        }

        return date('h:i A', strtotime($time));
    }
}
