<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['import_id', 'user_id', 'employee_ref_code', 'employee_name', 'present_days', 'week_off_days', 'absent_days', 'total_days', 'source_row', 'warnings'])]
class AttendanceConsolidateRow extends Model
{
    protected function casts(): array
    {
        return ['present_days' => 'decimal:2', 'week_off_days' => 'decimal:2', 'absent_days' => 'decimal:2', 'total_days' => 'decimal:2', 'warnings' => 'array'];
    }
}
